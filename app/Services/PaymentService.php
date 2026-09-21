<?php

namespace App\Services;

use App\DTOs\Payment\PaymentWebhookDTO;
use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function createPayment(int $userId, int $packageId, string $method = 'vietqr'): array
    {
        $package = MembershipPackage::find($packageId);

        if (! $package || ! $package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => ['Gói tập không tồn tại hoặc đã ngừng cung cấp.'],
            ]);
        }

        $user = User::find($userId);
        $transactionCode = 'TXN-'.date('YmdHis').'-'.strtoupper(Str::random(6));

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_code' => $transactionCode,
            'reference_id' => $package->id,
            'reference_type' => 'package_purchase',
            'amount' => $package->price,
            'currency' => 'VND',
            'payment_method' => $method,
            'status' => 'pending',
            'transaction_date' => now(),
            'notes' => 'Khởi tạo thanh toán gói '.$package->name,
        ]);

        // Tạo Payment Link VietQR từ biến môi trường (không hardcode thông tin tài khoản)
        $bankId = config('services.vietqr.bank_id');
        $accountNumber = config('services.vietqr.account_number');
        $accountName = urlencode(config('services.vietqr.account_name'));
        $template = config('services.vietqr.template', 'compact2');

        $vietQrUrl = "https://img.vietqr.io/image/{$bankId}-{$accountNumber}-{$template}.png"
            .'?amount='.(int) $package->price
            .'&addInfo='.$transactionCode
            .'&accountName='.$accountName;

        return [
            'transaction' => $transaction,
            'payment_url' => $vietQrUrl,
            'amount' => $package->price,
            'currency' => 'VND',
            'transaction_code' => $transactionCode,
            'instructions' => 'Vui lòng quét mã VietQR hoặc chuyển khoản với nội dung: '.$transactionCode,
        ];
    }

    public function handleWebhook(PaymentWebhookDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $transaction = Transaction::where('transaction_code', $dto->transactionCode)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw ValidationException::withMessages([
                    'transaction_code' => ['Mã giao dịch không tồn tại trên hệ thống.'],
                ]);
            }

            // 1. Chống Replay Attack & Đảm bảo tính Idempotency:
            if ($transaction->status === 'success') {
                return [
                    'success' => true,
                    'message' => 'Giao dịch đã được xử lý và kích hoạt trước đó (Idempotent - Replay ignored).',
                    'transaction' => $transaction,
                    'is_replayed' => true,
                ];
            }

            // 2. Xác thực Chữ ký số Webhook (HMAC SHA256) chống giả mạo payload
            $secretKey = config('services.payment.webhook_secret');

            if (! is_string($secretKey) || $secretKey === '') {
                throw ValidationException::withMessages([
                    'signature' => ['Webhook payment chưa được cấu hình secret key.'],
                ]);
            }

            $expectedSignature = hash_hmac('sha256', $dto->transactionCode.'|'.$dto->amount.'|'.$dto->status, $secretKey);

            if (empty($dto->signature) || ! hash_equals($expectedSignature, $dto->signature)) {
                throw ValidationException::withMessages([
                    'signature' => ['Chữ ký số webhook không hợp lệ (Signature verification failed).'],
                ]);
            }

            // Đối chiếu số tiền giao dịch
            if ((float) $dto->amount !== (float) $transaction->amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Số tiền thanh toán webhook không khớp với đơn hàng (Amount mismatch).'],
                ]);
            }

            $newStatus = match ($dto->status) {
                'success' => 'success',
                'failed' => 'failed',
                'pending' => 'pending',
                default => 'failed',
            };

            // 3. Cập nhật trạng thái và lưu audit trail đầy đủ
            $transaction->update([
                'status' => $newStatus,
                'payment_gateway_ref' => $dto->gatewayRef,
                'signature_hash' => $dto->signature,
                'webhook_payload' => $dto->rawPayload,
                'webhook_verified_at' => now(),
            ]);

            // 4. Nếu thanh toán thành công và là mua gói tập, tự động kích hoạt subscription duy nhất 1 lần
            if ($newStatus === 'success' && $transaction->reference_type === 'package_purchase') {
                $package = MembershipPackage::find($transaction->reference_id);
                if ($package) {
                    $startDate = Carbon::today();
                    $endDate = $startDate->copy()->addDays($package->duration_days);
                    $membershipCode = 'GYM-MB-'.strtoupper(Str::random(6));
                    $qrToken = 'QR-'.hash('sha256', $transaction->user_id.'-'.$membershipCode.'-'.time());

                    $subscription = MemberSubscription::create([
                        'user_id' => $transaction->user_id,
                        'package_id' => $package->id,
                        'membership_code' => $membershipCode,
                        'qr_token' => $qrToken,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'price_paid' => $transaction->amount,
                        'status' => 'active',
                        'remaining_pt_sessions' => $package->pt_sessions,
                    ]);

                    $transaction->update(['reference_id' => $subscription->id, 'reference_type' => 'subscription']);
                }
            }

            return [
                'success' => true,
                'message' => $newStatus === 'pending'
                    ? 'Webhook đã nhận nhưng giao dịch vẫn đang chờ xử lý.'
                    : 'Đã xử lý webhook thành công.',
                'transaction' => $transaction->fresh(),
                'is_replayed' => false,
            ];
        });
    }
}
