<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutPlanDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_plan_id',
        'day_number',
        'title',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
        ];
    }

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function planExercises(): HasMany
    {
        return $this->hasMany(WorkoutPlanExercise::class)->orderBy('order');
    }
}
