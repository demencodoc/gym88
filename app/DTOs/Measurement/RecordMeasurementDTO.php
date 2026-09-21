<?php

namespace App\DTOs\Measurement;

use Illuminate\Http\Request;

readonly class RecordMeasurementDTO
{
    public function __construct(
        public int $userId,
        public string $measuredAt,
        public float $weightKg,
        public float $heightCm,
        public ?float $bodyFatPercentage = null,
        public ?float $muscleMassKg = null,
        public ?float $chestCm = null,
        public ?float $waistCm = null,
        public ?float $hipsCm = null,
        public ?float $armsCm = null,
        public ?float $thighsCm = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            measuredAt: (string) ($request->validated('measured_at') ?? now()->toDateString()),
            weightKg: (float) $request->validated('weight_kg'),
            heightCm: (float) $request->validated('height_cm'),
            bodyFatPercentage: $request->validated('body_fat_percentage') ? (float) $request->validated('body_fat_percentage') : null,
            muscleMassKg: $request->validated('muscle_mass_kg') ? (float) $request->validated('muscle_mass_kg') : null,
            chestCm: $request->validated('chest_cm') ? (float) $request->validated('chest_cm') : null,
            waistCm: $request->validated('waist_cm') ? (float) $request->validated('waist_cm') : null,
            hipsCm: $request->validated('hips_cm') ? (float) $request->validated('hips_cm') : null,
            armsCm: $request->validated('arms_cm') ? (float) $request->validated('arms_cm') : null,
            thighsCm: $request->validated('thighs_cm') ? (float) $request->validated('thighs_cm') : null,
            notes: $request->validated('notes'),
        );
    }
}
