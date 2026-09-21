<?php

use App\Models\BodyMeasurement;
use App\Models\MediaFile;
use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Models\PTBooking;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('1. user cannot self-register with elevated role like admin, manager or staff', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Hacker User',
        'email' => 'hacker@example.com',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    $response->assertStatus(201);

    $user = User::where('email', 'hacker@example.com')->first();
    expect($user->role)->toBe('member');
});

test('2. payment webhook rejects payload missing signature or with mismatched amount', function () {
    $member = User::factory()->create(['role' => 'member']);
    $package = MembershipPackage::create([
        'name' => 'Gói 1 Tháng',
        'slug' => 'goi-1-thang',
        'price' => 500000,
        'duration_days' => 30,
        'pt_sessions' => 0,
        'is_active' => true,
    ]);

    $txn = Transaction::create([
        'user_id' => $member->id,
        'transaction_code' => 'TXN-TEST-999',
        'reference_id' => $package->id,
        'reference_type' => 'package_purchase',
        'amount' => 500000,
        'currency' => 'VND',
        'payment_method' => 'vietqr',
        'status' => 'pending',
        'transaction_date' => now(),
    ]);

    // Missing signature -> 422
    $resp1 = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => 'TXN-TEST-999',
        'amount' => 500000,
        'status' => 'success',
    ]);
    $resp1->assertStatus(422)->assertJsonValidationErrors(['signature']);

    // Invalid signature -> 422
    $resp2 = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => 'TXN-TEST-999',
        'amount' => 500000,
        'status' => 'success',
        'signature' => 'invalid-signature',
    ]);
    $resp2->assertStatus(422)->assertJsonValidationErrors(['signature']);

    // Amount mismatch -> 422
    $secret = config('services.payment.webhook_secret');
    $sigMismatch = hash_hmac('sha256', 'TXN-TEST-999|10000|success', $secret);

    $resp3 = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => 'TXN-TEST-999',
        'amount' => 10000,
        'status' => 'success',
        'signature' => $sigMismatch,
    ]);
    $resp3->assertStatus(422)->assertJsonValidationErrors(['amount']);
});

test('3. regular member cannot check-out another member', function () {
    $memberA = User::factory()->create(['role' => 'member']);
    $memberB = User::factory()->create(['role' => 'member']);

    $tokenA = $memberA->createToken('test')->plainTextToken;

    // Member A attempts to checkout Member B
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/v1/check-in/checkout', [
            'user_id' => $memberB->id,
        ]);

    // Should return 422 because checkOut will be forced for memberA, who has no open checkin
    $response->assertStatus(422);
});

test('4. member cannot book PT using another members subscription', function () {
    $memberA = User::factory()->create(['role' => 'member']);
    $memberB = User::factory()->create(['role' => 'member']);
    $trainer = User::factory()->create(['role' => 'trainer']);

    $package = MembershipPackage::create([
        'name' => 'Gói PT VIP',
        'slug' => 'goi-pt-vip',
        'price' => 2000000,
        'duration_days' => 30,
        'pt_sessions' => 5,
        'is_active' => true,
    ]);

    // Subscription belongs to Member B
    $subB = MemberSubscription::create([
        'user_id' => $memberB->id,
        'package_id' => $package->id,
        'membership_code' => 'MB-B-123',
        'qr_token' => 'QR-B-123',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'price_paid' => 2000000,
        'status' => 'active',
        'remaining_pt_sessions' => 5,
    ]);

    $tokenA = $memberA->createToken('test')->plainTextToken;

    // Member A attempts to use Member B's subscriptionId
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->postJson('/api/v1/pt-bookings', [
            'trainer_id' => $trainer->id,
            'subscription_id' => $subB->id,
            'start_time' => now()->addDay()->setHour(10)->setMinute(0)->toDateTimeString(),
            'end_time' => now()->addDay()->setHour(11)->setMinute(0)->toDateTimeString(),
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['subscription_id']);
});

test('5. PT booking overlap logic catches enclosing time slot', function () {
    $member1 = User::factory()->create(['role' => 'member']);
    $member2 = User::factory()->create(['role' => 'member']);
    $trainer = User::factory()->create(['role' => 'trainer']);

    $package = MembershipPackage::create([
        'name' => 'Gói Combo PT',
        'slug' => 'goi-combo-pt',
        'price' => 3000000,
        'duration_days' => 30,
        'pt_sessions' => 10,
        'is_active' => true,
    ]);

    $sub1 = MemberSubscription::create([
        'user_id' => $member1->id,
        'package_id' => $package->id,
        'membership_code' => 'MB-1',
        'qr_token' => 'QR-1',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'price_paid' => 3000000,
        'status' => 'active',
        'remaining_pt_sessions' => 10,
    ]);
    $sub2 = MemberSubscription::create([
        'user_id' => $member2->id,
        'package_id' => $package->id,
        'membership_code' => 'MB-2',
        'qr_token' => 'QR-2',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'price_paid' => 3000000,
        'status' => 'active',
        'remaining_pt_sessions' => 10,
    ]);

    // Existing booking: 09:00 to 12:00
    PTBooking::create([
        'user_id' => $member1->id,
        'trainer_id' => $trainer->id,
        'subscription_id' => $sub1->id,
        'start_time' => Carbon::tomorrow()->setHour(9)->setMinute(0)->toDateTimeString(),
        'end_time' => Carbon::tomorrow()->setHour(12)->setMinute(0)->toDateTimeString(),
        'status' => 'confirmed',
    ]);

    $token2 = $member2->createToken('test')->plainTextToken;

    // Member 2 tries to book 10:00 to 11:00 (enclosed inside 09:00 - 12:00)
    $response = $this->withHeader('Authorization', 'Bearer '.$token2)
        ->postJson('/api/v1/pt-bookings', [
            'trainer_id' => $trainer->id,
            'subscription_id' => $sub2->id,
            'start_time' => Carbon::tomorrow()->setHour(10)->setMinute(0)->toDateTimeString(),
            'end_time' => Carbon::tomorrow()->setHour(11)->setMinute(0)->toDateTimeString(),
        ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['start_time']);
});

test('6. regular member cannot directly activate subscription via POST /subscriptions', function () {
    $member = User::factory()->create(['role' => 'member']);
    $package = MembershipPackage::create([
        'name' => 'Gói Tập Thường',
        'slug' => 'goi-tap-thuong',
        'price' => 500000,
        'duration_days' => 30,
        'pt_sessions' => 0,
        'is_active' => true,
    ]);

    $token = $member->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/subscriptions', [
            'package_id' => $package->id,
        ]);

    $response->assertStatus(403);
});

test('7. freeze subscription requires valid subscription_id and updates status', function () {
    $member = User::factory()->create(['role' => 'member']);
    $package = MembershipPackage::create([
        'name' => 'Gói Tập Thường',
        'slug' => 'goi-tap-thuong',
        'price' => 500000,
        'duration_days' => 30,
        'pt_sessions' => 0,
        'is_active' => true,
    ]);

    $subscription = MemberSubscription::create([
        'user_id' => $member->id,
        'package_id' => $package->id,
        'membership_code' => 'MB-FREEZE-001',
        'qr_token' => 'QR-FREEZE-001',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'price_paid' => 500000,
        'status' => 'active',
        'remaining_pt_sessions' => 0,
    ]);

    $token = $member->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/subscriptions/freeze', [
            'subscription_id' => $subscription->id,
            'suspended_until' => now()->addDays(7)->toDateString(),
            'reason' => 'nghỉ 1 tuần',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('subscription.status', 'suspended');
});

test('8. private media files output protected API URLs instead of direct storage URLs', function () {
    $member = User::factory()->create(['role' => 'member']);
    $measurement = BodyMeasurement::create([
        'user_id' => $member->id,
        'measured_at' => '2026-09-01',
        'weight_kg' => 70,
        'height_cm' => 175,
    ]);

    $media = MediaFile::create([
        'user_id' => $member->id,
        'mediable_type' => BodyMeasurement::class,
        'mediable_id' => $measurement->id,
        'collection' => 'inbody_front',
        'file_name' => 'inbody.jpg',
        'file_path' => 'media/inbody/inbody.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 100,
        'file_type' => 'image',
    ]);

    expect($media->url)->toContain('/api/v1/media/'.$media->id.'/download');
});

test('8. freeze subscription DTO works with or without explicit subscription_id', function () {
    $member = User::factory()->create(['role' => 'member']);
    $package = MembershipPackage::create([
        'name' => 'Gói 1 Tháng',
        'slug' => 'goi-1-thang-freeze',
        'price' => 500000,
        'duration_days' => 30,
        'pt_sessions' => 0,
        'is_active' => true,
    ]);

    $sub = MemberSubscription::create([
        'user_id' => $member->id,
        'package_id' => $package->id,
        'membership_code' => 'MB-FREEZE-1',
        'qr_token' => 'QR-FREEZE-1',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'price_paid' => 500000,
        'status' => 'active',
        'remaining_pt_sessions' => 0,
    ]);

    $token = $member->createToken('test')->plainTextToken;

    // Call freeze without passing subscription_id in request body
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/subscriptions/freeze', [
            'suspended_at' => now()->toDateString(),
            'suspended_until' => now()->addDays(7)->toDateString(),
            'reason' => 'Đi công tác',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('subscription.status', 'suspended');
});
