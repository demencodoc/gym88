<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutPlanExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_plan_day_id',
        'exercise_id',
        'order',
        'target_sets',
        'target_reps',
        'rest_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'target_sets' => 'integer',
            'target_reps' => 'integer',
            'rest_seconds' => 'integer',
        ];
    }

    public function planDay(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlanDay::class, 'workout_plan_day_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
