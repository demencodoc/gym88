<?php

namespace App\Services;

use App\DTOs\CheckIn\CheckInDTO;
use App\Models\CheckIn;
use App\Models\MemberSubscription;
use App\Models\User;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CheckInService
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function checkIn(CheckInDTO $dto, ?User $staffUser = null): array
    {
        if ($staffUser && ! $staffUser->isStaff() && $dto->userId && (int) $dto->userId !== $staffUser->id) {
            throw ValidationException::withMessages([
                'user_id' => ['Bạn không có quyền check-in cho tài khoản khác.'],
            ]);
        }

        $subscription = null;

        if ($dto->code) {
            $subscription = $this->subscriptionRepository->findByQrOrCode($dto->code);
        } elseif ($dto->userId) {
            $subscription = MemberSubscription::with(['package', 'user'])
                ->where('user_id', $dto->userId)
                ->where('status', 'active')
                ->latest('id')
                ->first();
        }

        if (! $subscription) {
            throw ValidationException::withMessages([
                'code' => ['Mã thẻ hoặc mã QR không tồn tại trong hệ thống.'],
            ]);
        }

        if ($staffUser && ! $staffUser->isStaff() && $subscription->user_id !== $staffUser->id) {
            throw ValidationException::withMessages([
                'code' => ['Mã thẻ hoặc mã QR không thuộc sở hữu của bạn.'],
            ]);
        }

        if ($subscription->status === 'suspended') {
            throw ValidationException::withMessages([
                'status' => ['Gói tập của hội viên hiện đang trong trạng thái bảo lưu (đến ngày '.Carbon::parse($subscription->suspended_until)->format('d/m/Y').').'],
            ]);
        }

        if (! $subscription->isValidForCheckIn()) {
            throw ValidationException::withMessages([
                'status' => ['Gói tập của hội viên đã hết hạn hoặc chưa đến ngày bắt đầu.'],
            ]);
        }

        // Tạo bản ghi Check-in
        $checkIn = CheckIn::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'check_in_time' => now(),
            'method' => $dto->method,
            'notes' => $dto->notes,
        ]);

        return [
            'check_in' => $checkIn,
            'member' => $subscription->user->load('avatar'),
            'package' => $subscription->package,
            'days_remaining' => $subscription->days_remaining,
        ];
    }

    public function checkOut(int $userId): CheckIn
    {
        $latestCheckIn = CheckIn::where('user_id', $userId)
            ->whereNull('check_out_time')
            ->latest('check_in_time')
            ->first();

        if (! $latestCheckIn) {
            throw ValidationException::withMessages([
                'check_out' => ['Bạn chưa có lượt check-in nào đang mở hoặc đã check-out trước đó.'],
            ]);
        }

        $latestCheckIn->update([
            'check_out_time' => now(),
        ]);

        return $latestCheckIn;
    }

    public function getHistory(int $userId, int $perPage = 15)
    {
        return CheckIn::with('subscription.package')
            ->where('user_id', $userId)
            ->orderByDesc('check_in_time')
            ->paginate($perPage);
    }
}
