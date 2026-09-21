<?php

namespace App\Models;

use App\Observers\ClassBookingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ClassBookingObserver::class])]
class ClassBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_class_schedule_id',
        'user_id',
        'status',
        'booked_at',
    ];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(GymClassSchedule::class, 'gym_class_schedule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
