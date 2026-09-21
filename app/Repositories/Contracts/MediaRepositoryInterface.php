<?php

namespace App\Repositories\Contracts;

use App\Models\MediaFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MediaRepositoryInterface
{
    public function create(array $data): MediaFile;

    public function findById(int $id): ?MediaFile;

    public function delete(MediaFile $media): bool;

    public function getMediaList(?string $collection = null, ?int $userId = null, ?string $mediableType = null, ?int $mediableId = null, int $perPage = 20): LengthAwarePaginator;
}
