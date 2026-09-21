<?php

namespace App\Repositories\Contracts;

use App\Models\Exercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WorkoutRepositoryInterface
{
    public function getExercises(?string $muscleGroup = null, ?string $difficulty = null, int $perPage = 20): LengthAwarePaginator;

    public function findExerciseById(int $id): ?Exercise;

    public function getMuscleGroups(): array;

    public function getWorkoutPlans(int $userId): Collection;

    public function findWorkoutPlanById(int $id): ?WorkoutPlan;

    public function createSession(array $data): WorkoutSession;

    public function findSessionById(int $id): ?WorkoutSession;

    public function getUserSessions(int $userId, int $perPage = 15): LengthAwarePaginator;
}
