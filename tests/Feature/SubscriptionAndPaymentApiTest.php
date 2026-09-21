<?php

use App\Models\MembershipPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can browse packages, create payment and activate via webhook', function () {
    $user = User::factory()->create(['is_active' => true]);
    $package = MembershipPackage::create([
        'name' => 'Gói 1 Tháng',
        'slug' => 'goi-1-thang',
        'description' => 'Mô tả gói tập',
        'price' => 500000,
        'duration_days' => 30,
        'has_pt' => true,
        'pt_sessions' => 2,
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    // 1. Xem danh sách gói tập
    $packagesResponse = $this->getJson('/api/v1/packages');
    $packagesResponse->assertStatus(200)
        ->assertJsonCount(1, 'data');

    // 2. Tạo đơn thanh toán VietQR
    $paymentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/payments/create', [
            'package_id' => $package->id,
            'payment_method' => 'vietqr',
        ]);

    $paymentResponse->assertStatus(201)
        ->assertJsonStructure([
            'transaction',
            'payment_url',
            'transaction_code',
        ]);

    $txnCode = $paymentResponse->json('transaction_code');

    $secretKey = config('services.payment.webhook_secret');
    $signature = hash_hmac('sha256', $txnCode.'|500000|success', $secretKey);

    // 3. Giả lập cổng thanh toán gửi Webhook thành công
    $webhookResponse = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => $txnCode,
        'amount' => 500000,
        'status' => 'success',
        'gateway_ref' => 'MB-BANK-998811',
        'signature' => $signature,
    ]);

    $webhookResponse->assertStatus(200)
        ->assertJsonPath('success', true);

    // 4. Kiểm tra gói tập hội viên tự động được kích hoạt
    $this->assertDatabaseHas('member_subscriptions', [
        'user_id' => $user->id,
        'package_id' => $package->id,
        'status' => 'active',
        'remaining_pt_sessions' => 2,
    ]);

    // 5. Lấy Dynamic QR để quét cổng
    $qrResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/subscriptions/dynamic-qr');

    $qrResponse->assertStatus(200)
        ->assertJsonStructure([
            'membership_code',
            'qr_token',
            'expires_at',
            'days_remaining',
        ]);
});
