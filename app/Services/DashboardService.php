<?php

namespace App\Services;

use App\Models\ClassBooking;
use App\Models\Notification;
use App\Models\PTBooking;
use App\Models\User;
use App\Models\WorkoutSession;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboard(User $user): array
    {
        $activeSubscription = $user->activeSubscription;

        // Thống kê tập luyện tuần này (Thứ 2 -> Chủ Nhật)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weekSessions = WorkoutSession::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->whereBetween('started_at', [$startOfWeek, $endOfWeek])
            ->get();

        $weekWorkoutsCount = $weekSessions->count();
        $weekCaloriesBurned = $weekSessions->sum('calories_burned');
        $weekDurationMinutes = $weekSessions->sum('duration_minutes');

        // Lịch tập PT sắp diễn ra
        $upcomingPTBooking = PTBooking::with('trainer.avatar')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->first();

        // Lớp học nhóm sắp diễn ra mà hội viên đã đặt
        $upcomingClassBooking = ClassBooking::with(['schedule.gymClass.coverImage', 'schedule.trainer'])
            ->where('user_id', $user->id)
            ->where('status', 'booked')
            ->whereHas('schedule', function ($q) {
                $q->where('start_time', '>=', now());
            })
            ->latest('id')
            ->first();

        // Số thông báo chưa đọc (badge count)
        $unreadNotificationsCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
            'membership' => $activeSubscription ? [
                'has_active_package' => true,
                'package_name' => $activeSubscription->package->name,
                'membership_code' => $activeSubscription->membership_code,
                'qr_token' => $activeSubscription->qr_token,
                'start_date' => $activeSubscription->start_date->format('d/m/Y'),
                'end_date' => $activeSubscription->end_date->format('d/m/Y'),
                'days_remaining' => $activeSubscription->days_remaining,
                'remaining_pt_sessions' => $activeSubscription->remaining_pt_sessions,
                'status' => $activeSubscription->status,
            ] : [
                'has_active_package' => false,
            ],
            'weekly_stats' => [
                'workouts_completed' => $weekWorkoutsCount,
                'calories_burned' => (float) $weekCaloriesBurned,
                'total_minutes' => (int) $weekDurationMinutes,
            ],
            'upcoming_schedules' => [
                'pt_session' => $upcomingPTBooking ? [
                    'id' => $upcomingPTBooking->id,
                    'trainer_name' => $upcomingPTBooking->trainer->name,
                    'trainer_avatar' => $upcomingPTBooking->trainer->avatar_url,
                    'start_time' => $upcomingPTBooking->start_time->format('H:i d/m/Y'),
                    'end_time' => $upcomingPTBooking->end_time->format('H:i d/m/Y'),
                    'status' => $upcomingPTBooking->status,
                ] : null,
                'group_class' => $upcomingClassBooking ? [
                    'id' => $upcomingClassBooking->id,
                    'class_name' => $upcomingClassBooking->schedule->gymClass->name,
                    'cover_image' => $upcomingClassBooking->schedule->gymClass->cover_image_url,
                    'room' => $upcomingClassBooking->schedule->room,
                    'trainer_name' => $upcomingClassBooking->schedule->trainer->name,
                    'start_time' => $upcomingClassBooking->schedule->start_time->format('H:i d/m/Y'),
                ] : null,
            ],
            'unread_notifications_count' => $unreadNotificationsCount,
        ];
    }
}
