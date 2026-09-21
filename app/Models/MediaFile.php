<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mediable_type',
        'mediable_id',
        'collection',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'file_type',
        'width',
        'height',
        'duration_seconds',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'download_count' => 'integer',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get accessible public or storage URL.
     */
    public function getUrlAttribute(): string
    {
        $privateCollections = ['inbody_front', 'inbody_side', 'inbody_back'];

        if (in_array($this->collection, $privateCollections, true)) {
            return url('/api/v1/media/'.$this->id.'/download');
        }

        return Storage::disk($this->disk)->url($this->file_path);
    }

    /**
     * Atomic increment for download counter.
     */
    public function recordDownload(): void
    {
        DB::table('media_files')
            ->where('id', $this->id)
            ->increment('download_count');
    }
}
