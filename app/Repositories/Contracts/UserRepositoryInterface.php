<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmailOrPhone(string $login): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): bool;

    public function getTrainers(int $perPage = 15): LengthAwarePaginator;
}
