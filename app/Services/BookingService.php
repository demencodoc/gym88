<?php

namespace App\Services;

use App\DTOs\Booking\BookClassScheduleDTO;
use App\DTOs\Booking\PTBookingDTO;
use App\Models\ClassBooking;
use App\Models\GymClassSchedule;
use App\Models\MemberSubscription;
use App\Models\PTBooking;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    public function bookPT(PTBookingDTO $dto): PTBooking
    {
        return DB::transaction(function () use ($dto) {
            $member = User::find($dto->memberId);
            $trainer = User::where('id', $dto->trainerId)->where('role', 'trainer')->first();

            if (! $trainer) {
                throw ValidationException::withMessages([
                    'trainer_id' => ['Huấn luyện viên không tồn tại trong hệ thống.'],
                ]);
            }

            // Tìm gói tập có PT còn buổi và đúng của member (Pessimistic Locking lockForUpdate)
            $subscription = null;
            if ($dto->subscriptionId) {
                $subscription = MemberSubscription::where('id', $dto->subscriptionId)
                    ->lockForUpdate()
                    ->first();

                if (! $subscription || $subscription->user_id !== $dto->memberId || $subscription->status !== 'active') {
                    throw ValidationException::withMessages([
                        'subscription_id' => ['Gói tập không hợp lệ, không thuộc sở hữu của bạn hoặc không ở trạng thái kích hoạt.'],
                    ]);
                }
            } else {
                $subscription = MemberSubscription::where('user_id', $dto->memberId)
                    ->where('status', 'active')
                    ->where('remaining_pt_sessions', '>', 0)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $subscription || $subscription->remaining_pt_sessions <= 0) {
                throw ValidationException::withMessages([
                    'subscription_id' => ['Bạn không còn số buổi tập PT khả dụng trong gói tập. Vui lòng đăng ký thêm.'],
                ]);
            }

            $startTime = Carbon::parse($dto->startTime);
            $endTime = Carbon::parse($dto->endTime);

            if ($endTime->lte($startTime)) {
                throw ValidationException::withMessages([
                    'end_time' => ['Thời gian kết thúc ca tập phải sau thời gian bắt đầu.'],
                ]);
            }

            // Kiểm tra xem PT có bị trùng lịch hẹn khác không (lockForUpdate để ngăn trùng ca tập đồng thời)
            $overlapBooking = PTBooking::where('trainer_id', $trainer->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->lockForUpdate()
                ->exists();

            if ($overlapBooking) {
                throw ValidationException::withMessages([
                    'start_time' => ['Huấn luyện viên đã có lịch hẹn khác trong khung giờ này.'],
                ]);
            }

            // Trừ 1 buổi PT trong hợp đồng
            $subscription->decrement('remaining_pt_sessions');

            return $this->bookingRepository->createPTBooking([
                'user_id' => $dto->memberId,
                'trainer_id' => $trainer->id,
                'subscription_id' => $subscription->id,
                'start_time' => $startTime->toDateTimeString(),
                'end_time' => $endTime->toDateTimeString(),
                'status' => 'confirmed',
            ]);
        });
    }

    public function cancelPTBooking(int $bookingId, int $userId, ?string $reason = null): PTBooking
    {
        return DB::transaction(function () use ($bookingId, $userId, $reason) {
            $booking = PTBooking::where('id', $bookingId)->lockForUpdate()->first();

            if (! $booking || ($booking->user_id !== $userId && $booking->trainer_id !== $userId)) {
                throw ValidationException::withMessages([
                    'booking_id' => ['Lịch hẹn không tồn tại hoặc bạn không có quyền hủy.'],
                ]);
            }

            if (in_array($booking->status, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Lịch hẹn này đã hoàn thành hoặc đã bị hủy trước đó.'],
                ]);
            }

            // Hoàn lại buổi PT nếu hủy trước 6 tiếng
            $hoursNotice = now()->diffInHours($booking->start_time, false);
            if ($hoursNotice >= 6 && $booking->subscription_id) {
                $sub = MemberSubscription::where('id', $booking->subscription_id)->lockForUpdate()->first();
                if ($sub) {
                    $sub->increment('remaining_pt_sessions');
                }
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_reason' => $reason ?? 'Hội viên yêu cầu hủy ca tập.',
            ]);

            return $booking->fresh();
        });
    }

    public function reviewPTBooking(int $bookingId, int $userId, int $rating, ?string $notes = null): PTBooking
    {
        $booking = $this->bookingRepository->findPTBookingById($bookingId);

        if (! $booking || $booking->user_id !== $userId) {
            throw ValidationException::withMessages([
                'booking_id' => ['Lịch hẹn không tồn tại hoặc không phải ca tập của bạn.'],
            ]);
        }

        $booking->update([
            'rating' => min(5, max(1, $rating)),
            'review_notes' => $notes,
        ]);

        return $booking->fresh();
    }

    public function bookClass(BookClassScheduleDTO $dto): ClassBooking
    {
        return DB::transaction(function () use ($dto) {
            $schedule = GymClassSchedule::where('id', $dto->gymClassScheduleId)
                ->lockForUpdate()
                ->first();

            if (! $schedule || $schedule->status !== 'scheduled') {
                throw ValidationException::withMessages([
                    'schedule' => ['Lớp học không tồn tại hoặc đã đóng đăng ký.'],
                ]);
            }

            if ($schedule->isFull()) {
                throw ValidationException::withMessages([
                    'schedule' => ['Ca học này đã đủ sĩ số tối đa ('.$schedule->max_capacity.' người).'],
                ]);
            }

            $existing = $this->bookingRepository->findClassBooking($schedule->id, $dto->userId);

            if ($existing && $existing->status === 'booked') {
                throw ValidationException::withMessages([
                    'booking' => ['Bạn đã đặt chỗ tham gia lớp học này rồi.'],
                ]);
            }

            if ($existing && $existing->status === 'cancelled') {
                $existing->update([
                    'status' => 'booked',
                    'booked_at' => now(),
                ]);

                return $existing;
            }

            return $this->bookingRepository->createClassBooking([
                'gym_class_schedule_id' => $schedule->id,
                'user_id' => $dto->userId,
                'status' => 'booked',
                'booked_at' => now(),
            ]);
        });
    }

    public function cancelClass(int $scheduleId, int $userId): bool
    {
        $booking = $this->bookingRepository->findClassBooking($scheduleId, $userId);

        if (! $booking || $booking->status !== 'booked') {
            throw ValidationException::withMessages([
                'booking' => ['Bạn chưa đặt chỗ hoặc đã hủy lớp học này rồi.'],
            ]);
        }

        $booking->update(['status' => 'cancelled']);

        return true;
    }
}
