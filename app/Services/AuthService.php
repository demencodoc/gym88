<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\UpdateProfileDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function register(RegisterDTO $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => $dto->password,
            'role' => 'member',
            'gender' => $dto->gender,
            'date_of_birth' => $dto->dateOfBirth,
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile_app')->plainTextToken;

        return [
            'user' => $user->load('avatar'),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmailOrPhone($dto->login);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Thông tin đăng nhập hoặc mật khẩu không chính xác.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Tài khoản của bạn hiện đang bị khóa. Vui lòng liên hệ lễ tân.'],
            ]);
        }

        $token = $user->createToken($dto->deviceName)->plainTextToken;

        return [
            'user' => $user->load(['avatar', 'activeSubscription.package']),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function updateProfile(User $user, UpdateProfileDTO $dto): User
    {
        $data = array_filter([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'gender' => $dto->gender,
            'date_of_birth' => $dto->dateOfBirth,
            'address' => $dto->address,
            'emergency_contact' => $dto->emergencyContact,
        ], fn ($val) => $val !== null);

        $this->userRepository->update($user, $data);

        return $user->fresh(['avatar', 'activeSubscription.package']);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không chính xác.'],
            ]);
        }

        return $this->userRepository->update($user, [
            'password' => $newPassword,
        ]);
    }

    /**
     * Gửi email chứa link đặt lại mật khẩu.
     */
    public function forgotPassword(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::INVALID_USER) {
            return;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    /**
     * Đặt lại mật khẩu bằng token được gửi qua email.
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $newPassword],
            function (User $user, string $password): void {
                $this->userRepository->update($user, ['password' => $password]);
                $user->tokens()->delete(); // Thu hồi toàn bộ token cũ sau khi đặt lại mật khẩu
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }
    }

    /**
     * Thu hồi tất cả Sanctum token (đăng xuất khỏi mọi thiết bị).
     */
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
