<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'set_number' => $this->set_number,
            'reps_completed' => $this->reps_completed,
            'weight_kg' => (float) $this->weight_kg,
            'rpe' => $this->rpe,
            'is_completed' => $this->is_completed,
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
        ];
    }
}
