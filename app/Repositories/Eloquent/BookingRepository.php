<?php

namespace App\Repositories\Eloquent;

use App\Models\ClassBooking;
use App\Models\GymClassSchedule;
use App\Models\PTBooking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function getUpcomingSchedules(?string $date = null): Collection
    {
        $query = GymClassSchedule::with(['gymClass.coverImage', 'trainer.avatar'])
            ->where('status', 'scheduled')
            ->where('start_time', '>=', now())
            ->orderBy('start_time');

        if ($date) {
            $query->whereDate('start_time', $date);
        }

        return $query->get();
    }

    public function findScheduleById(int $id): ?GymClassSchedule
    {
        return GymClassSchedule::with(['gymClass.coverImage', 'trainer.avatar', 'bookings'])->find($id);
    }

    public function createClassBooking(array $data): ClassBooking
    {
        return ClassBooking::create($data);
    }

    public function findClassBooking(int $scheduleId, int $userId): ?ClassBooking
    {
        return ClassBooking::where('gym_class_schedule_id', $scheduleId)
            ->where('user_id', $userId)
            ->first();
    }

    public function getUserClassBookings(int $userId): Collection
    {
        return ClassBooking::with(['schedule.gymClass.coverImage', 'schedule.trainer'])
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();
    }

    public function createPTBooking(array $data): PTBooking
    {
        return PTBooking::create($data);
    }

    public function findPTBookingById(int $id): ?PTBooking
    {
        return PTBooking::with(['trainer.avatar', 'member.avatar', 'subscription'])->find($id);
    }

    public function getUserPTBookings(int $userId): Collection
    {
        return PTBooking::with(['trainer.avatar', 'subscription'])
            ->where('user_id', $userId)
            ->orderByDesc('start_time')
            ->get();
    }

    public function getTrainerPTBookings(int $trainerId): Collection
    {
        return PTBooking::with(['member.avatar', 'subscription'])
            ->where('trainer_id', $trainerId)
            ->orderByDesc('start_time')
            ->get();
    }
}
