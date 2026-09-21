<?php

namespace App\Repositories\Eloquent;

use App\Models\Exercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Repositories\Contracts\WorkoutRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WorkoutRepository implements WorkoutRepositoryInterface
{
    public function getExercises(?string $muscleGroup = null, ?string $difficulty = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Exercise::with(['video', 'thumbnail'])
            ->where('is_active', true);

        if ($muscleGroup) {
            $query->where('muscle_group', $muscleGroup);
        }

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        return $query->paginate($perPage);
    }

    public function findExerciseById(int $id): ?Exercise
    {
        return Exercise::with(['video', 'thumbnail'])->find($id);
    }

    public function getMuscleGroups(): array
    {
        return [
            'chest' => 'Ngực (Chest)',
            'back' => 'Lưng - Xô (Back)',
            'legs' => 'Chân - Đùi (Legs)',
            'shoulders' => 'Vai (Shoulders)',
            'biceps' => 'Tay trước (Biceps)',
            'triceps' => 'Tay sau (Triceps)',
            'abs' => 'Bụng (Abs)',
            'cardio' => 'Tim mạch (Cardio)',
            'full_body' => 'Toàn thân (Full Body)',
        ];
    }

    public function getWorkoutPlans(int $userId): Collection
    {
        return WorkoutPlan::with(['days.planExercises.exercise.thumbnail'])
            ->where('is_public', true)
            ->orWhere('assigned_to', $userId)
            ->orWhere('creator_id', $userId)
            ->get();
    }

    public function findWorkoutPlanById(int $id): ?WorkoutPlan
    {
        return WorkoutPlan::with(['days.planExercises.exercise.thumbnail', 'days.planExercises.exercise.video'])->find($id);
    }

    public function createSession(array $data): WorkoutSession
    {
        return WorkoutSession::create($data);
    }

    public function findSessionById(int $id): ?WorkoutSession
    {
        return WorkoutSession::with(['logs.exercise.thumbnail'])->find($id);
    }

    public function getUserSessions(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WorkoutSession::with(['logs.exercise'])
            ->where('user_id', $userId)
            ->orderByDesc('started_at')
            ->paginate($perPage);
    }
}
