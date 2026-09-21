<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class GymClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(GymClassSchedule::class);
    }

    /**
     * Polymorphic cover image relation.
     */
    public function coverImage(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'class_cover');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->coverImage?->url;
    }
}
