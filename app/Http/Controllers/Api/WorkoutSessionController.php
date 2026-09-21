<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Workout\LogSetDTO;
use App\DTOs\Workout\StartSessionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workout\CompleteSessionRequest;
use App\Http\Requests\Workout\LogSetRequest;
use App\Http\Requests\Workout\StartSessionRequest;
use App\Http\Resources\WorkoutLogResource;
use App\Http\Resources\WorkoutSessionResource;
use App\Models\WorkoutSession;
use App\Services\WorkoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkoutSessionController extends Controller
{
    public function __construct(
        protected WorkoutService $workoutService
    ) {}

    public function start(StartSessionRequest $request): JsonResponse
    {
        $dto = StartSessionDTO::fromRequest($request, $request->user()->id);
        $session = $this->workoutService->startSession($dto);

        return response()->json([
            'message' => 'Bắt đầu buổi tập thành công.',
            'session' => new WorkoutSessionResource($session),
        ], 201);
    }

    public function logSet(int $id, LogSetRequest $request): JsonResponse
    {
        $dto = LogSetDTO::fromRequest($request);
        $log = $this->workoutService->logSet($id, $request->user()->id, $dto);

        return response()->json([
            'message' => 'Ghi nhận set tập thành công.',
            'log' => new WorkoutLogResource($log->load('exercise')),
        ], 201);
    }

    public function complete(int $id, CompleteSessionRequest $request): JsonResponse
    {
        $session = $this->workoutService->completeSession(
            $id,
            $request->user()->id,
            $request->validated('rating'),
            $request->validated('notes')
        );

        return response()->json([
            'message' => 'Hoàn thành buổi tập xuất sắc!',
            'session' => new WorkoutSessionResource($session),
        ]);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $sessions = WorkoutSession::with(['logs.exercise.thumbnail'])
            ->where('user_id', $request->user()->id)
            ->latest('started_at')
            ->paginate(15);

        return WorkoutSessionResource::collection($sessions);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $session = WorkoutSession::with(['logs.exercise.thumbnail'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'session' => new WorkoutSessionResource($session),
        ]);
    }
}
