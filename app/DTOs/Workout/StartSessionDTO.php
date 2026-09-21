<?php

namespace App\DTOs\Workout;

use Illuminate\Http\Request;

readonly class StartSessionDTO
{
    public function __construct(
        public int $userId,
        public string $title,
        public ?int $workoutPlanDayId = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            title: (string) ($request->validated('title') ?? ('Buổi tập ngày '.now()->format('d/m/Y'))),
            workoutPlanDayId: $request->validated('workout_plan_day_id'),
            notes: $request->validated('notes'),
        );
    }
}
