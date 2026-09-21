<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'muscle_group',
        'difficulty',
        'equipment',
        'instructions',
        'calories_burn_per_minute',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'calories_burn_per_minute' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Polymorphic video relation.
     */
    public function video(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'exercise_video');
    }

    /**
     * Polymorphic thumbnail relation.
     */
    public function thumbnail(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'exercise_thumb');
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video?->url;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail?->url;
    }

    public function planExercises(): HasMany
    {
        return $this->hasMany(WorkoutPlanExercise::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }
}
