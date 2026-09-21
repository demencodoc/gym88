<?php

use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('payment webhook validates signature, audits payload, activates subscription and prevents replay attacks', function () {
    $user = User::factory()->create();
    $package = MembershipPackage::create([
        'name' => 'Gói Gold 3 Tháng',
        'slug' => 'gold-3m',
        'price' => 1500000,
        'duration_days' => 90,
        'has_pt' => true,
        'pt_sessions' => 3,
        'is_active' => true,
    ]);

    $txnCode = 'TXN-TEST-AUDIT-001';

    $transaction = Transaction::create([
        'user_id' => $user->id,
        'transaction_code' => $txnCode,
        'reference_id' => $package->id,
        'reference_type' => 'package_purchase',
        'amount' => 1500000,
        'currency' => 'VND',
        'payment_method' => 'vietqr',
        'status' => 'pending',
        'transaction_date' => now(),
    ]);

    $secretKey = config('services.payment.webhook_secret');
    $validSignature = hash_hmac('sha256', $txnCode.'|1500000|success', $secretKey);

    // CASE 1: Chữ ký giả mạo (Invalid Signature) -> Bị chặn 422
    $invalidResponse = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => $txnCode,
        'amount' => 1500000,
        'status' => 'success',
        'signature' => 'fake-hacker-signature-12345',
    ]);

    $invalidResponse->assertStatus(422)
        ->assertJsonValidationErrors(['signature']);
    expect($transaction->fresh()->status)->toBe('pending');

    // CASE 2: Webhook hợp lệ lần đầu -> Kích hoạt gói, lưu audit trail
    $validResponse = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => $txnCode,
        'amount' => 1500000,
        'status' => 'success',
        'gateway_ref' => 'MB-BANK-998877',
        'signature' => $validSignature,
    ]);

    $validResponse->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('is_replayed', false);

    // Kiểm tra audit trail đầy đủ trong database
    $freshTxn = $transaction->fresh();
    expect($freshTxn->status)->toBe('success')
        ->and($freshTxn->payment_gateway_ref)->toBe('MB-BANK-998877')
        ->and($freshTxn->signature_hash)->toBe($validSignature)
        ->and($freshTxn->webhook_payload)->not->toBeNull()
        ->and($freshTxn->webhook_verified_at)->not->toBeNull();

    // Kiểm tra subscription được tạo đúng 1 bản ghi
    expect(MemberSubscription::where('user_id', $user->id)->count())->toBe(1);
    $sub = MemberSubscription::where('user_id', $user->id)->first();
    expect($sub->status)->toBe('active')
        ->and($sub->remaining_pt_sessions)->toBe(3);

    // CASE 3: Replay Attack (Gửi lại chính webhook đó lần 2)
    // Hệ thống phải nhận diện Idempotent và KHÔNG ĐƯỢC tạo thêm subscription
    $replayResponse = $this->postJson('/api/v1/payments/webhook', [
        'transaction_code' => $txnCode,
        'amount' => 1500000,
        'status' => 'success',
        'gateway_ref' => 'MB-BANK-998877',
        'signature' => $validSignature,
    ]);

    $replayResponse->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('is_replayed', true);

    // Số lượng subscription trong database vẫn PHẢI là 1 (chống kích hoạt lặp/trùng)
    expect(MemberSubscription::where('user_id', $user->id)->count())->toBe(1);
});
