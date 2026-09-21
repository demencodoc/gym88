<?php

namespace App\DTOs\Workout;

use Illuminate\Http\Request;

readonly class LogSetDTO
{
    public function __construct(
        public int $exerciseId,
        public int $setNumber,
        public int $repsCompleted,
        public float $weightKg = 0.0,
        public ?int $rpe = null,
        public bool $isCompleted = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            exerciseId: (int) $request->validated('exercise_id'),
            setNumber: (int) $request->validated('set_number'),
            repsCompleted: (int) $request->validated('reps_completed'),
            weightKg: (float) ($request->validated('weight_kg') ?? 0.0),
            rpe: $request->validated('rpe') ? (int) $request->validated('rpe') : null,
            isCompleted: (bool) ($request->validated('is_completed') ?? true),
        );
    }
}
