<?php

namespace App\Policies;

use App\Models\MediaFile;
use App\Models\PTBooking;
use App\Models\User;

class MediaPolicy
{
    /**
     * Determine whether the user can view/stream/download the media file.
     */
    public function view(User $user, MediaFile $media): bool
    {
        // Nhóm Public Media: Mọi người dùng đã xác thực đều được phép xem/tải
        $publicCollections = ['class_cover', 'exercise_video', 'exercise_thumb', 'avatar', 'pt_cert'];
        if (in_array($media->collection, $publicCollections, true)) {
            return true;
        }

        // Nhóm Private Media: inbody_front, inbody_side, inbody_back
        // 1. Chính chủ sở hữu
        if ($media->user_id === $user->id) {
            return true;
        }

        // 2. Admin hoặc Manager
        if ($user->isAdmin()) {
            return true;
        }

        // 3. Huấn luyện viên (PT) có ca tập với hội viên này
        if ($user->isTrainer()) {
            return PTBooking::where('trainer_id', $user->id)
                ->where('user_id', $media->user_id)
                ->exists();
        }

        return false;
    }

    public function download(User $user, MediaFile $media): bool
    {
        return $this->view($user, $media);
    }

    public function stream(User $user, MediaFile $media): bool
    {
        return $this->view($user, $media);
    }

    /**
     * Determine whether the user can delete the media file.
     */
    public function delete(User $user, MediaFile $media): bool
    {
        return $media->user_id === $user->id || $user->isAdmin();
    }
}
