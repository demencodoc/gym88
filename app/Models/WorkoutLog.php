<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'set_number',
        'reps_completed',
        'weight_kg',
        'rpe',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'reps_completed' => 'integer',
            'weight_kg' => 'decimal:2',
            'rpe' => 'integer',
            'is_completed' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
