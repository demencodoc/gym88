<?php

namespace App\Repositories\Contracts;

use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionRepositoryInterface
{
    public function getActivePackages(): Collection;

    public function findPackageById(int $id): ?MembershipPackage;

    public function createSubscription(array $data): MemberSubscription;

    public function findSubscriptionById(int $id): ?MemberSubscription;

    public function findByQrOrCode(string $code): ?MemberSubscription;

    public function getUserSubscriptions(int $userId): Collection;
}
