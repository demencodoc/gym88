<?php

namespace App\DTOs\Booking;

use Illuminate\Http\Request;

readonly class PTBookingDTO
{
    public function __construct(
        public int $memberId,
        public int $trainerId,
        public string $startTime,
        public string $endTime,
        public ?int $subscriptionId = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request, int $memberId): self
    {
        return new self(
            memberId: $memberId,
            trainerId: (int) $request->validated('trainer_id'),
            startTime: (string) $request->validated('start_time'),
            endTime: (string) $request->validated('end_time'),
            subscriptionId: $request->validated('subscription_id') ? (int) $request->validated('subscription_id') : null,
            notes: $request->validated('notes'),
        );
    }
}
