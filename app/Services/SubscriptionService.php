<?php

namespace App\Services;

use App\DTOs\Membership\CreateSubscriptionDTO;
use App\DTOs\Membership\FreezeSubscriptionDTO;
use App\Models\MemberSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function subscribe(CreateSubscriptionDTO $dto): array
    {
        $package = $this->subscriptionRepository->findPackageById($dto->packageId);

        if (! $package || ! $package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => ['Gói tập không tồn tại hoặc đã ngừng hoạt động.'],
            ]);
        }

        $startDate = $dto->startDate ? Carbon::parse($dto->startDate) : Carbon::today();
        $endDate = $startDate->copy()->addDays($package->duration_days);

        $membershipCode = 'GYM-MB-'.strtoupper(Str::random(6));
        $qrToken = 'QR-'.hash('sha256', $dto->userId.'-'.$membershipCode.'-'.time());

        $subscription = $this->subscriptionRepository->createSubscription([
            'user_id' => $dto->userId,
            'package_id' => $package->id,
            'membership_code' => $membershipCode,
            'qr_token' => $qrToken,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'price_paid' => $package->price, // Snapshot price!
            'status' => 'active',
            'remaining_pt_sessions' => $package->pt_sessions,
        ]);

        // Tạo giao dịch thanh toán ban đầu
        $transaction = Transaction::create([
            'user_id' => $dto->userId,
            'transaction_code' => 'TXN-'.date('YmdHis').'-'.rand(1000, 9999),
            'reference_id' => $subscription->id,
            'reference_type' => 'subscription',
            'amount' => $package->price,
            'currency' => 'VND',
            'payment_method' => $dto->paymentMethod,
            'status' => 'success', // Hoặc pending nếu chờ cổng
            'transaction_date' => now(),
            'notes' => 'Thanh toán đăng ký gói: '.$package->name,
        ]);

        return [
            'subscription' => $subscription->load('package'),
            'transaction' => $transaction,
        ];
    }

    public function freeze(FreezeSubscriptionDTO $dto, int $userId): MemberSubscription
    {
        $subscription = null;

        if ($dto->subscriptionId) {
            $subscription = $this->subscriptionRepository->findSubscriptionById($dto->subscriptionId);
        } else {
            $subscription = MemberSubscription::where('user_id', $userId)
                ->where('status', 'active')
                ->latest('id')
                ->first();
        }

        if (! $subscription || $subscription->user_id !== $userId) {
            throw ValidationException::withMessages([
                'subscription_id' => ['Gói tập không tồn tại hoặc bạn không có quyền bảo lưu.'],
            ]);
        }

        if ($subscription->status !== 'active') {
            throw ValidationException::withMessages([
                'subscription_id' => ['Chỉ có thể bảo lưu gói tập đang kích hoạt.'],
            ]);
        }

        $suspendedAt = Carbon::parse($dto->suspendedAt);
        $suspendedUntil = Carbon::parse($dto->suspendedUntil);

        if ($suspendedUntil->lte($suspendedAt)) {
            throw ValidationException::withMessages([
                'suspended_until' => ['Ngày kết thúc bảo lưu phải sau ngày bắt đầu bảo lưu.'],
            ]);
        }

        $subscription->update([
            'status' => 'suspended',
            'suspended_at' => $suspendedAt->toDateString(),
            'suspended_until' => $suspendedUntil->toDateString(),
            'notes' => ($subscription->notes ? $subscription->notes.' | ' : '').'Bảo lưu: '.$dto->reason,
        ]);

        return $subscription->fresh('package');
    }

    public function unfreeze(int $subscriptionId, int $userId): MemberSubscription
    {
        $subscription = $this->subscriptionRepository->findSubscriptionById($subscriptionId);

        if (! $subscription || $subscription->user_id !== $userId) {
            throw ValidationException::withMessages([
                'subscription_id' => ['Gói tập không tồn tại hoặc bạn không có quyền mở lại.'],
            ]);
        }

        if ($subscription->status !== 'suspended') {
            throw ValidationException::withMessages([
                'subscription_id' => ['Gói tập này không ở trạng thái bảo lưu.'],
            ]);
        }

        // Kéo dài ngày kết thúc tương ứng với số ngày đã bảo lưu thực tế
        $suspendedAt = Carbon::parse($subscription->suspended_at);
        $today = Carbon::today();
        $daysFrozen = max(0, $suspendedAt->diffInDays($today));

        $newEndDate = Carbon::parse($subscription->end_date)->addDays($daysFrozen);

        $subscription->update([
            'status' => 'active',
            'end_date' => $newEndDate->toDateString(),
            'suspended_at' => null,
            'suspended_until' => null,
        ]);

        return $subscription->fresh('package');
    }

    public function getDynamicQr(User $user): array
    {
        $activeSubscription = $user->activeSubscription;

        if (! $activeSubscription) {
            throw ValidationException::withMessages([
                'membership' => ['Bạn hiện không có gói tập nào đang hoạt động.'],
            ]);
        }

        // Dynamic QR Token xoay vòng (refresh theo thời gian thực)
        $timestamp = time();
        $dynamicToken = 'DYN-'.hash('sha256', $user->id.'-'.$activeSubscription->membership_code.'-'.$timestamp);

        $activeSubscription->update(['qr_token' => $dynamicToken]);

        return [
            'membership_code' => $activeSubscription->membership_code,
            'qr_token' => $dynamicToken,
            'package_name' => $activeSubscription->package->name,
            'expires_at' => $activeSubscription->end_date->format('d/m/Y'),
            'days_remaining' => $activeSubscription->days_remaining,
            'remaining_pt_sessions' => $activeSubscription->remaining_pt_sessions,
        ];
    }
}
