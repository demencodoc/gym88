<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Media\UploadMediaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\BatchUploadRequest;
use App\Http\Requests\Media\UploadMediaRequest;
use App\Http\Resources\MediaFileResource;
use App\Models\MediaFile;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = MediaFile::query();

        if ($request->filled('collection')) {
            $query->where('collection', $request->input('collection'));
        }

        if ($request->filled('mediable_type')) {
            $query->where('mediable_type', $request->input('mediable_type'));
        }

        if ($request->filled('mediable_id')) {
            $query->where('mediable_id', $request->input('mediable_id'));
        }

        // Kiểm soát bảo mật danh sách media:
        // Nếu không phải admin, giới hạn media private chỉ xem được file của chính mình hoặc public
        if (! $user->isAdmin()) {
            $publicCollections = ['avatar', 'class_cover', 'exercise_video', 'exercise_thumb', 'pt_cert'];
            $query->where(function ($q) use ($user, $publicCollections) {
                $q->whereIn('collection', $publicCollections)
                    ->orWhere('user_id', $user->id);
            });
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $files = $query->latest('id')->paginate($request->input('per_page', 20));

        return MediaFileResource::collection($files);
    }

    public function show(int $id): JsonResponse
    {
        $media = MediaFile::findOrFail($id);

        Gate::authorize('view', $media);

        return response()->json([
            'media' => new MediaFileResource($media),
        ]);
    }

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $collection = $request->validated('collection');
        $mediableType = $request->validated('mediable_type');
        $mediableId = $request->validated('mediable_id');

        // Gán tự động mediable nếu là avatar hoặc pt_cert
        if (in_array($collection, ['avatar', 'pt_cert'], true) && ! $mediableType) {
            $mediableType = User::class;
            $mediableId = $request->user()->id;
        }

        $dto = new UploadMediaDTO(
            file: $request->file('file'),
            userId: $request->user()->id,
            collection: $collection,
            mediableType: $mediableType,
            mediableId: $mediableId ? (int) $mediableId : null,
        );

        $media = $this->mediaService->upload($dto);

        return response()->json([
            'message' => 'Tải lên tệp thành công.',
            'media' => new MediaFileResource($media),
        ], 201);
    }

    public function uploadInBodyBatch(BatchUploadRequest $request): JsonResponse
    {
        $uploaded = $this->mediaService->batchUploadInBody(
            userId: $request->user()->id,
            bodyMeasurementId: (int) $request->validated('body_measurement_id'),
            front: $request->file('front'),
            side: $request->file('side'),
            back: $request->file('back'),
        );

        return response()->json([
            'message' => 'Tải lên bộ ảnh InBody thành công.',
            'photos' => array_map(fn ($media) => new MediaFileResource($media), $uploaded),
        ], 201);
    }

    public function download(int $id, Request $request): BinaryFileResponse
    {
        $media = MediaFile::findOrFail($id);

        Gate::authorize('download', $media);

        return $this->mediaService->download($media);
    }

    public function stream(int $id, Request $request): mixed
    {
        $media = MediaFile::findOrFail($id);

        Gate::authorize('stream', $media);

        return $this->mediaService->stream($media, $request);
    }

    public function signedUrl(int $id, Request $request): JsonResponse
    {
        $media = MediaFile::findOrFail($id);

        Gate::authorize('view', $media);

        $url = $this->mediaService->generateSignedUrl($media, 30);

        return response()->json([
            'signed_url' => $url,
            'expires_in_minutes' => 30,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $media = MediaFile::findOrFail($id);

        Gate::authorize('delete', $media);

        Storage::disk($media->disk)->delete($media->file_path);
        $media->delete();

        return response()->json([
            'message' => 'Đã xóa tệp tin thành công.',
        ]);
    }
}
