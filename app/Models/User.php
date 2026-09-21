<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'phone',
    'password',
    'role',
    'gender',
    'date_of_birth',
    'address',
    'emergency_contact',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<UserFactory> */
    use CanResetPasswordTrait, HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Polymorphic avatar relation.
     */
    public function avatar(): MorphOne
    {
        return $this->morphOne(MediaFile::class, 'mediable')->where('collection', 'avatar');
    }

    /**
     * Polymorphic PT certificates relation.
     */
    public function certificates(): MorphMany
    {
        return $this->morphMany(MediaFile::class, 'mediable')->where('collection', 'pt_cert');
    }

    /**
     * Helper accessor for avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar?->url;
    }

    public function getPtCertUrlAttribute(): ?string
    {
        return $this->certificates()->first()?->url;
    }

    public function getRatingAvgAttribute(): float
    {
        return (float) ($this->ptBookingsAsTrainer()->whereNotNull('rating')->avg('rating') ?: 5.0);
    }

    public function getTotalClientsAttribute(): int
    {
        return $this->ptBookingsAsTrainer()->distinct('user_id')->count('user_id');
    }

    public function getSpecializationAttribute(): ?string
    {
        return $this->attributes['specialization'] ?? 'Fitness & Bodybuilding';
    }

    public function getBioAttribute(): ?string
    {
        return $this->attributes['bio'] ?? 'Huấn luyện viên thể hình chuyên nghiệp.';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MemberSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MemberSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->latestOfMany();
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function ptBookingsAsMember(): HasMany
    {
        return $this->hasMany(PTBooking::class, 'user_id');
    }

    public function ptBookingsAsTrainer(): HasMany
    {
        return $this->hasMany(PTBooking::class, 'trainer_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(TrainerAvailability::class, 'trainer_id');
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isTrainer(): bool
    {
        return $this->role === 'trainer';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'manager', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'manager'], true);
    }
}
