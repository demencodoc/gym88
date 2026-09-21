<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_class_id',
        'trainer_id',
        'room',
        'start_time',
        'end_time',
        'max_capacity',
        'current_bookings_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_capacity' => 'integer',
            'current_bookings_count' => 'integer',
        ];
    }

    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function isFull(): bool
    {
        return $this->current_bookings_count >= $this->max_capacity;
    }

    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_capacity - $this->current_bookings_count);
    }
}
