<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'membership_code',
        'qr_token',
        'start_date',
        'end_date',
        'price_paid',
        'status',
        'remaining_pt_sessions',
        'suspended_at',
        'suspended_until',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price_paid' => 'decimal:2',
            'remaining_pt_sessions' => 'integer',
            'suspended_at' => 'date',
            'suspended_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(MembershipPackage::class, 'package_id');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'subscription_id');
    }

    public function ptBookings(): HasMany
    {
        return $this->hasMany(PTBooking::class, 'subscription_id');
    }

    public function isValidForCheckIn(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = Carbon::today();

        return $today->betweenIncluded($this->start_date, $this->end_date);
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $diff = Carbon::today()->diffInDays($this->end_date, false);

        return max(0, (int) $diff);
    }
}
