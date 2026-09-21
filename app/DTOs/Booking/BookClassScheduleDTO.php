<?php

namespace App\DTOs\Booking;

use Illuminate\Http\Request;

readonly class BookClassScheduleDTO
{
    public function __construct(
        public int $userId,
        public int $gymClassScheduleId,
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            gymClassScheduleId: (int) ($request->route('id') ?? $request->validated('gym_class_schedule_id')),
        );
    }
}
