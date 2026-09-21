<?php

use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can check in and check out using qr token', function () {
    $user = User::factory()->create(['is_active' => true]);
    $package = MembershipPackage::create([
        'name' => 'Gói Tập VIP',
        'slug' => 'goi-vip',
        'price' => 1000000,
        'duration_days' => 30,
        'has_pt' => false,
        'pt_sessions' => 0,
        'is_active' => true,
    ]);

    $subscription = MemberSubscription::create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'membership_code' => 'MB-TEST-001',
        'qr_token' => 'DYN-QR-TOKEN-12345',
        'start_date' => Carbon::yesterday()->toDateString(),
        'end_date' => Carbon::tomorrow()->addDays(20)->toDateString(),
        'price_paid' => 1000000,
        'status' => 'active',
    ]);

    $token = $user->createToken('test')->plainTextToken;

    // 1. Quét mã Check-in
    $checkInResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in', [
            'code' => 'DYN-QR-TOKEN-12345',
            'method' => 'dynamic_qr',
        ]);

    $checkInResponse->assertStatus(200)
        ->assertJsonPath('check_in.user_id', $user->id);

    $this->assertDatabaseHas('check_ins', [
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
    ]);

    // 2. Quét mã Check-out
    $checkOutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/check-in/checkout');

    $checkOutResponse->assertStatus(200);

    // 3. Xem lịch sử Check-in
    $historyResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/check-in/history');

    $historyResponse->assertStatus(200)
        ->assertJsonCount(1, 'data');
});
