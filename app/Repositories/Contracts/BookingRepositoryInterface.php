<?php

namespace App\Repositories\Contracts;

use App\Models\ClassBooking;
use App\Models\GymClassSchedule;
use App\Models\PTBooking;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface
{
    public function getUpcomingSchedules(?string $date = null): Collection;

    public function findScheduleById(int $id): ?GymClassSchedule;

    public function createClassBooking(array $data): ClassBooking;

    public function findClassBooking(int $scheduleId, int $userId): ?ClassBooking;

    public function getUserClassBookings(int $userId): Collection;

    public function createPTBooking(array $data): PTBooking;

    public function findPTBookingById(int $id): ?PTBooking;

    public function getUserPTBookings(int $userId): Collection;

    public function getTrainerPTBookings(int $trainerId): Collection;
}
