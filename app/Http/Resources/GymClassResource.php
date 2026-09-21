<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'max_capacity' => $this->max_capacity,
            'duration_minutes' => $this->duration_minutes,
            'difficulty' => $this->difficulty,
            'is_active' => $this->is_active,
            'cover_url' => $this->cover_url,
            'schedules' => ClassScheduleResource::collection($this->whenLoaded('schedules')),
        ];
    }
}
