<?php

namespace App\DTOs\Membership;

use Illuminate\Http\Request;

readonly class FreezeSubscriptionDTO
{
    public function __construct(
        public ?int $subscriptionId = null,
        public string $suspendedAt = '',
        public string $suspendedUntil = '',
        public ?string $reason = null,
    ) {}

    public static function fromRequest(Request $request, ?int $subscriptionId = null): self
    {
        $id = $subscriptionId ?? $request->validated('subscription_id') ?? $request->input('subscription_id');

        return new self(
            subscriptionId: $id ? (int) $id : null,
            suspendedAt: (string) ($request->validated('suspended_at') ?? now()->toDateString()),
            suspendedUntil: (string) $request->validated('suspended_until'),
            reason: $request->validated('reason'),
        );
    }
}
