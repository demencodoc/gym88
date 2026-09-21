<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainerAvailabilityResource;
use App\Http\Resources\TrainerResource;
use App\Models\TrainerAvailability;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $trainers = User::with(['avatar', 'availabilities'])
            ->where('role', 'trainer')
            ->where('is_active', true)
            ->get();

        return TrainerResource::collection($trainers);
    }

    public function show(int $id): JsonResponse
    {
        $trainer = User::with(['avatar', 'availabilities'])
            ->where('role', 'trainer')
            ->findOrFail($id);

        return response()->json([
            'trainer' => new TrainerResource($trainer),
        ]);
    }

    public function availabilities(int $id): AnonymousResourceCollection
    {
        $availabilities = TrainerAvailability::where('trainer_id', $id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return TrainerAvailabilityResource::collection($availabilities);
    }
}
