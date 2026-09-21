<?php

namespace App\Repositories\Eloquent;

use App\Models\MediaFile;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MediaRepository implements MediaRepositoryInterface
{
    public function create(array $data): MediaFile
    {
        return MediaFile::create($data);
    }

    public function findById(int $id): ?MediaFile
    {
        return MediaFile::find($id);
    }

    public function delete(MediaFile $media): bool
    {
        return $media->delete();
    }

    public function getMediaList(?string $collection = null, ?int $userId = null, ?string $mediableType = null, ?int $mediableId = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = MediaFile::query();

        if ($collection) {
            $query->where('collection', $collection);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($mediableType && $mediableId) {
            $query->where('mediable_type', $mediableType)
                ->where('mediable_id', $mediableId);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }
}
