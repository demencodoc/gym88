<?php

namespace App\DTOs\Media;

use Illuminate\Http\UploadedFile;

readonly class UploadMediaDTO
{
    public function __construct(
        public UploadedFile $file,
        public int $userId,
        public string $collection = 'default',
        public ?string $mediableType = null,
        public ?int $mediableId = null,
    ) {}
}
