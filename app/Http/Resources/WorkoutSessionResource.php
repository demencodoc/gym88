<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'calories_burned' => (float) $this->calories_burned,
            'rating' => $this->rating,
            'notes' => $this->notes,
            'logs' => WorkoutLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
