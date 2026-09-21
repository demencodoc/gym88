<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Measurement\RecordMeasurementDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Measurement\RecordMeasurementRequest;
use App\Http\Resources\BodyMeasurementResource;
use App\Models\BodyMeasurement;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BodyMeasurementController extends Controller
{
    public function __construct(
        protected WorkoutService $workoutService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $measurements = BodyMeasurement::with(['frontPhoto', 'sidePhoto', 'backPhoto'])
            ->where('user_id', $request->user()->id)
            ->latest('measured_at')
            ->paginate(15);

        return BodyMeasurementResource::collection($measurements);
    }

    public function store(RecordMeasurementRequest $request): JsonResponse
    {
        $dto = RecordMeasurementDTO::fromRequest($request, $request->user()->id);
        $measurement = $this->workoutService->recordMeasurement($dto);

        return response()->json([
            'message' => 'Lưu chỉ số InBody thành công.',
            'measurement' => new BodyMeasurementResource($measurement),
        ], 201);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $measurement = BodyMeasurement::with(['frontPhoto', 'sidePhoto', 'backPhoto'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'measurement' => new BodyMeasurementResource($measurement),
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $chartData = $this->workoutService->getProgressChart($request->user()->id);

        return response()->json($chartData);
    }
}
