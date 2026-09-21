<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\GymClassResource;
use App\Models\GymClass;
use App\Models\GymClassSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GymClassController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $classes = GymClass::with(['coverImage'])
            ->where('is_active', true)
            ->get();

        return GymClassResource::collection($classes);
    }

    public function show(int $id): JsonResponse
    {
        $class = GymClass::with(['coverImage', 'schedules' => function ($q) {
            $q->where('start_time', '>=', now())
                ->where('status', 'scheduled')
                ->orderBy('start_time')
                ->with('trainer.avatar');
        }])->findOrFail($id);

        return response()->json([
            'class' => new GymClassResource($class),
        ]);
    }

    public function schedules(Request $request): AnonymousResourceCollection
    {
        $query = GymClassSchedule::with(['gymClass.coverImage', 'trainer.avatar'])
            ->where('status', 'scheduled')
            ->where('start_time', '>=', now());

        if ($request->filled('class_id')) {
            $query->where('gym_class_id', $request->input('class_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('start_time', Carbon::parse($request->input('date')));
        }

        $schedules = $query->orderBy('start_time')->paginate(20);

        return ClassScheduleResource::collection($schedules);
    }
}
