<?php

namespace App\Observers;

use App\Models\ClassBooking;
use App\Models\GymClassSchedule;

class ClassBookingObserver
{
    public function created(ClassBooking $booking): void
    {
        if ($booking->status === 'booked') {
            GymClassSchedule::where('id', $booking->gym_class_schedule_id)
                ->increment('current_bookings_count');
        }
    }

    public function updated(ClassBooking $booking): void
    {
        if ($booking->wasChanged('status')) {
            $originalStatus = $booking->getOriginal('status');
            $newStatus = $booking->status;

            if ($originalStatus === 'booked' && in_array($newStatus, ['cancelled'], true)) {
                GymClassSchedule::where('id', $booking->gym_class_schedule_id)
                    ->decrement('current_bookings_count');
            } elseif ($originalStatus === 'cancelled' && $newStatus === 'booked') {
                GymClassSchedule::where('id', $booking->gym_class_schedule_id)
                    ->increment('current_bookings_count');
            }
        }
    }

    public function deleted(ClassBooking $booking): void
    {
        if ($booking->status === 'booked') {
            GymClassSchedule::where('id', $booking->gym_class_schedule_id)
                ->decrement('current_bookings_count');
        }
    }
}
