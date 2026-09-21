<?php

namespace App\Repositories\Eloquent;

use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function getActivePackages(): Collection
    {
        return MembershipPackage::where('is_active', true)->get();
    }

    public function findPackageById(int $id): ?MembershipPackage
    {
        return MembershipPackage::find($id);
    }

    public function createSubscription(array $data): MemberSubscription
    {
        return MemberSubscription::create($data);
    }

    public function findSubscriptionById(int $id): ?MemberSubscription
    {
        return MemberSubscription::with(['package', 'user'])->find($id);
    }

    public function findByQrOrCode(string $code): ?MemberSubscription
    {
        return MemberSubscription::with(['package', 'user'])
            ->where('qr_token', $code)
            ->orWhere('membership_code', $code)
            ->first();
    }

    public function getUserSubscriptions(int $userId): Collection
    {
        return MemberSubscription::with('package')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();
    }
}
