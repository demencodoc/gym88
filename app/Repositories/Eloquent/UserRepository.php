<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with(['avatar', 'certificates', 'activeSubscription.package'])->find($id);
    }

    public function findByEmailOrPhone(string $login): ?User
    {
        return User::where('email', $login)
            ->orWhere('phone', $login)
            ->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function getTrainers(int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', 'trainer')
            ->where('is_active', true)
            ->with(['avatar', 'certificates', 'availabilities'])
            ->paginate($perPage);
    }
}
