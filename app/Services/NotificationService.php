<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        string $channel = 'in_app'
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'channel' => $channel,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
