<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'assigned_to',
        'title',
        'description',
        'goal',
        'level',
        'duration_weeks',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'duration_weeks' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkoutPlanDay::class)->orderBy('day_number');
    }
}
