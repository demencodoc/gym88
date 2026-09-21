<?php

namespace App\DTOs\Payment;

use Illuminate\Http\Request;

readonly class PaymentWebhookDTO
{
    public function __construct(
        public string $transactionCode,
        public float $amount,
        public string $status, // success, failed
        public string $paymentMethod,
        public ?string $gatewayRef = null,
        public ?string $signature = null,
        public array $rawPayload = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            transactionCode: (string) $request->input('transaction_code'),
            amount: (float) $request->input('amount', 0),
            status: (string) $request->input('status', 'success'),
            paymentMethod: (string) $request->input('payment_method', 'vietqr'),
            gatewayRef: $request->input('gateway_ref'),
            signature: $request->header('X-Signature') ?? $request->input('signature'),
            rawPayload: $request->all(),
        );
    }
}
