<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExerciseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exercise::with(['thumbnail', 'video'])
            ->where('is_active', true);

        if ($request->filled('muscle_group')) {
            $query->where('muscle_group', $request->input('muscle_group'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->input('difficulty'));
        }

        if ($request->filled('search')) {
            $keyword = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                    ->orWhere('equipment', 'like', $keyword)
                    ->orWhere('instructions', 'like', $keyword);
            });
        }

        $exercises = $query->orderBy('name')->paginate(20);

        return ExerciseResource::collection($exercises);
    }

    public function show(int $id): JsonResponse
    {
        $exercise = Exercise::with(['thumbnail', 'video'])->findOrFail($id);

        return response()->json([
            'exercise' => new ExerciseResource($exercise),
        ]);
    }

    public function muscleGroups(): JsonResponse
    {
        $groups = Exercise::where('is_active', true)
            ->distinct()
            ->pluck('muscle_group')
            ->filter()
            ->values();

        return response()->json([
            'muscle_groups' => $groups,
        ]);
    }
}
