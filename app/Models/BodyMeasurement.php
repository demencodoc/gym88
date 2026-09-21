<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BodyMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'measured_at',
        'weight_kg',
        'height_cm',
        'bmi',
        'body_fat_percentage',
        'muscle_mass_kg',
        'chest_cm',
        'waist_cm',
        'hips_cm',
        'arms_cm',
        'thighs_cm',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'bmi' => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
            'muscle_mass_kg' => 'decimal:2',
            'chest_cm' => 'decimal:2',
            'waist_cm' => 'decimal:2',
            'hips_cm' => 'decimal:2',
            'arms_cm' => 'decimal:2',
            'thighs_cm' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic front photo relation.
     */
    public function frontPhoto(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'inbody_front');
    }

    /**
     * Polymorphic side photo relation.
     */
    public function sidePhoto(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'inbody_side');
    }

    /**
     * Polymorphic back photo relation.
     */
    public function backPhoto(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'inbody_back');
    }

    public function getFrontPhotoUrlAttribute(): ?string
    {
        return $this->frontPhoto?->url;
    }

    public function getSidePhotoUrlAttribute(): ?string
    {
        return $this->sidePhoto?->url;
    }

    public function getBackPhotoUrlAttribute(): ?string
    {
        return $this->backPhoto?->url;
    }
}
