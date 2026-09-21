<?php

namespace App\Services;

use App\DTOs\Media\UploadMediaDTO;
use App\Models\BodyMeasurement;
use App\Models\MediaFile;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaService
{
    public function __construct(
        protected MediaRepositoryInterface $mediaRepository
    ) {}

    /**
     * Upload single media with magic bytes sniffing and dimension extraction.
     */
    public function upload(UploadMediaDTO $dto): MediaFile
    {
        $file = $dto->file;

        // 1. Kiểm tra magic bytes bằng finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $allowedVideoMimes = ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-msvideo'];

        $isImage = in_array($detectedMime, $allowedImageMimes, true);
        $isVideo = in_array($detectedMime, $allowedVideoMimes, true);

        if (! $isImage && ! $isVideo) {
            throw ValidationException::withMessages([
                'file' => ['Tệp tin không đúng định dạng nhị phân hợp lệ (phát hiện MIME: '.$detectedMime.').'],
            ]);
        }

        // 2. Trích xuất metadata kích thước ảnh nếu là ảnh
        $width = null;
        $height = null;
        if ($isImage) {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize) {
                $width = $imageSize[0];
                $height = $imageSize[1];
            }
        }

        // 3. Hash tên file an toàn chống path traversal
        $extension = $file->getClientOriginalExtension() ?: ($isImage ? 'jpg' : 'mp4');
        $safeFileName = Str::random(40).'.'.strtolower($extension);
        $folderPath = 'media/'.$dto->collection.'/'.date('Y-m');

        // 4. Lưu trữ trên Storage (disk public hoặc s3)
        $disk = config('filesystems.default', 'public');
        $storedPath = $file->storeAs($folderPath, $safeFileName, $disk);

        return $this->mediaRepository->create([
            'user_id' => $dto->userId,
            'mediable_type' => $dto->mediableType,
            'mediable_id' => $dto->mediableId,
            'collection' => $dto->collection,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'disk' => $disk,
            'mime_type' => $detectedMime,
            'file_size' => $file->getSize(),
            'file_type' => $isImage ? 'image' : 'video',
            'width' => $width,
            'height' => $height,
            'duration_seconds' => null,
            'download_count' => 0,
        ]);
    }

    /**
     * Batch upload InBody photos (front, side, back) for a measurement record.
     */
    public function batchUploadInBody(
        int $userId,
        int $bodyMeasurementId,
        ?UploadedFile $front = null,
        ?UploadedFile $side = null,
        ?UploadedFile $back = null
    ): array {
        $measurement = BodyMeasurement::where('id', $bodyMeasurementId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $uploaded = [];

        if ($front) {
            $uploaded['front'] = $this->upload(new UploadMediaDTO(
                file: $front,
                userId: $userId,
                collection: 'inbody_front',
                mediableType: BodyMeasurement::class,
                mediableId: $measurement->id,
            ));
        }

        if ($side) {
            $uploaded['side'] = $this->upload(new UploadMediaDTO(
                file: $side,
                userId: $userId,
                collection: 'inbody_side',
                mediableType: BodyMeasurement::class,
                mediableId: $measurement->id,
            ));
        }

        if ($back) {
            $uploaded['back'] = $this->upload(new UploadMediaDTO(
                file: $back,
                userId: $userId,
                collection: 'inbody_back',
                mediableType: BodyMeasurement::class,
                mediableId: $measurement->id,
            ));
        }

        return $uploaded;
    }

    /**
     * Handle download with atomic increment and attachment headers.
     */
    public function download(MediaFile $media): BinaryFileResponse
    {
        $media->recordDownload(); // Atomic increment

        $fullPath = Storage::disk($media->disk)->path($media->file_path);

        return response()->download($fullPath, $media->file_name, [
            'Content-Type' => $media->mime_type,
        ]);
    }

    /**
     * Stream video without blocking PHP workers:
     * 1. S3 / CloudFront: Redirect directly to presigned temporary URL (0ms PHP worker load).
     * 2. Nginx (Production): Returns X-Accel-Redirect header so Nginx handles byte-range I/O (< 1ms worker release).
     * 3. Local/Dev: Graceful fallback to HTTP 206 Partial Content chunk stream.
     */
    public function stream(MediaFile $media, Request $request): mixed
    {
        // 1. Nếu lưu trữ trên Cloud Storage (S3 / CloudFront): Giải phóng PHP worker bằng cách redirect sang signed URL
        if ($media->disk === 's3') {
            $presignedUrl = Storage::disk('s3')->temporaryUrl($media->file_path, now()->addMinutes(60));

            return redirect()->away($presignedUrl);
        }

        // 2. Chế độ Nginx X-Accel-Redirect (Production):
        // Nginx tự động xử lý byte-range, giải phóng PHP worker ngay lập tức
        $useXAccel = config('media.use_x_accel', env('USE_X_ACCEL_REDIRECT', false))
            || $request->header('X-Accel-Supported') === 'true';

        if ($useXAccel) {
            return response('', 200, [
                'X-Accel-Redirect' => '/protected_media/'.$media->file_path,
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        // 3. Chế độ Fallback cho Local Development (php artisan serve / dev không có Nginx)
        $fullPath = Storage::disk($media->disk)->path($media->file_path);

        if (! file_exists($fullPath)) {
            abort(404, 'Tệp video không tồn tại.');
        }

        $size = filesize($fullPath);
        $file = fopen($fullPath, 'rb');

        $range = $request->header('Range');

        if (! $range) {
            return response()->file($fullPath, [
                'Content-Type' => $media->mime_type,
                'Accept-Ranges' => 'bytes',
                'Content-Length' => $size,
            ]);
        }

        // Xử lý byte range: bytes=start-end
        [, $range] = explode('=', $range, 2);
        [$start, $end] = explode('-', $range);

        $start = (int) $start;
        $end = ($end === '') ? ($size - 1) : (int) $end;
        $length = ($end - $start) + 1;

        fseek($file, $start);

        return response()->stream(function () use ($file, $length) {
            $buffer = 1024 * 64; // 64KB chunks
            $remaining = $length;
            while ($remaining > 0 && ! feof($file)) {
                $read = min($buffer, $remaining);
                echo fread($file, $read);
                flush();
                $remaining -= $read;
            }
            fclose($file);
        }, 206, [
            'Content-Type' => $media->mime_type,
            'Content-Range' => "bytes {$start}-{$end}/{$size}",
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $length,
        ]);
    }

    /**
     * Generate signed temporary URL (valid for X minutes).
     */
    public function generateSignedUrl(MediaFile $media, int $minutes = 15): string
    {
        return URL::temporarySignedRoute(
            'api.v1.media.download',
            now()->addMinutes($minutes),
            ['id' => $media->id]
        );
    }
}
