<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PTBooking extends Model
{
    use HasFactory;

    protected $table = 'pt_bookings';

    protected $fillable = [
        'user_id',
        'trainer_id',
        'subscription_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'cancelled_reason',
        'rating',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MemberSubscription::class);
    }
}
