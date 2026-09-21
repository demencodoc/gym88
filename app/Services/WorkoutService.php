<?php

namespace App\Services;

use App\DTOs\Measurement\RecordMeasurementDTO;
use App\DTOs\Workout\LogSetDTO;
use App\DTOs\Workout\StartSessionDTO;
use App\Models\BodyMeasurement;
use App\Models\WorkoutLog;
use App\Models\WorkoutSession;
use App\Repositories\Contracts\WorkoutRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WorkoutService
{
    public function __construct(
        protected WorkoutRepositoryInterface $workoutRepository
    ) {}

    public function startSession(StartSessionDTO $dto): WorkoutSession
    {
        return $this->workoutRepository->createSession([
            'user_id' => $dto->userId,
            'workout_plan_day_id' => $dto->workoutPlanDayId,
            'title' => $dto->title,
            'started_at' => now(),
            'notes' => $dto->notes,
        ]);
    }

    public function logSet(int $sessionId, int $userId, LogSetDTO $dto): WorkoutLog
    {
        $session = $this->workoutRepository->findSessionById($sessionId);

        if (! $session || $session->user_id !== $userId) {
            throw ValidationException::withMessages([
                'session_id' => ['Buổi tập không tồn tại hoặc bạn không có quyền ghi chép.'],
            ]);
        }

        $exercise = $this->workoutRepository->findExerciseById($dto->exerciseId);

        if (! $exercise) {
            throw ValidationException::withMessages([
                'exercise_id' => ['Bài tập không tồn tại.'],
            ]);
        }

        return WorkoutLog::create([
            'workout_session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'set_number' => $dto->setNumber,
            'reps_completed' => $dto->repsCompleted,
            'weight_kg' => $dto->weightKg,
            'rpe' => $dto->rpe,
            'is_completed' => $dto->isCompleted,
        ]);
    }

    public function completeSession(int $sessionId, int $userId, ?int $rating = null, ?string $notes = null): WorkoutSession
    {
        $session = $this->workoutRepository->findSessionById($sessionId);

        if (! $session || $session->user_id !== $userId) {
            throw ValidationException::withMessages([
                'session_id' => ['Buổi tập không tồn tại hoặc bạn không có quyền cập nhật.'],
            ]);
        }

        $completedAt = now();
        $durationMinutes = max(1, (int) Carbon::parse($session->started_at)->diffInMinutes($completedAt));

        // Tính toán lượng Calo tiêu hao dựa trên các bài tập và thời gian
        $totalCalories = 0.0;
        $uniqueExercises = $session->logs->pluck('exercise')->unique('id');

        if ($uniqueExercises->isNotEmpty()) {
            $avgBurnPerMin = $uniqueExercises->avg('calories_burn_per_minute') ?: 6.5;
            $totalCalories = round($avgBurnPerMin * $durationMinutes, 2);
        } else {
            $totalCalories = round(6.0 * $durationMinutes, 2);
        }

        $session->update([
            'completed_at' => $completedAt,
            'duration_minutes' => $durationMinutes,
            'calories_burned' => $totalCalories,
            'rating' => $rating ?? $session->rating,
            'notes' => $notes ?? $session->notes,
        ]);

        return $session->fresh(['logs.exercise.thumbnail']);
    }

    public function recordMeasurement(RecordMeasurementDTO $dto): BodyMeasurement
    {
        // Tự động tính BMI: weight / (height/100)^2
        $heightInMeters = $dto->heightCm / 100;
        $bmi = round($dto->weightKg / ($heightInMeters * $heightInMeters), 2);

        return BodyMeasurement::create([
            'user_id' => $dto->userId,
            'measured_at' => $dto->measuredAt,
            'weight_kg' => $dto->weightKg,
            'height_cm' => $dto->heightCm,
            'bmi' => $bmi,
            'body_fat_percentage' => $dto->bodyFatPercentage,
            'muscle_mass_kg' => $dto->muscleMassKg,
            'chest_cm' => $dto->chestCm,
            'waist_cm' => $dto->waistCm,
            'hips_cm' => $dto->hipsCm,
            'arms_cm' => $dto->armsCm,
            'thighs_cm' => $dto->thighsCm,
            'notes' => $dto->notes,
        ]);
    }

    public function getProgressChart(int $userId): array
    {
        $measurements = BodyMeasurement::where('user_id', $userId)
            ->orderBy('measured_at')
            ->get();

        return [
            'labels' => $measurements->map(fn ($m) => Carbon::parse($m->measured_at)->format('d/m'))->values(),
            'weight' => $measurements->pluck('weight_kg')->values(),
            'body_fat' => $measurements->pluck('body_fat_percentage')->values(),
            'muscle_mass' => $measurements->pluck('muscle_mass_kg')->values(),
            'bmi' => $measurements->pluck('bmi')->values(),
        ];
    }
}
