<?php

namespace App\DTOs\Membership;

use Illuminate\Http\Request;

readonly class CreateSubscriptionDTO
{
    public function __construct(
        public int $userId,
        public int $packageId,
        public ?string $startDate = null,
        public string $paymentMethod = 'vietqr',
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            packageId: (int) $request->validated('package_id'),
            startDate: $request->validated('start_date'),
            paymentMethod: (string) ($request->validated('payment_method') ?? 'vietqr'),
        );
    }
}
