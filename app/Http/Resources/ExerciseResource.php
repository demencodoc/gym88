<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'muscle_group' => $this->muscle_group,
            'difficulty' => $this->difficulty,
            'equipment' => $this->equipment,
            'instructions' => $this->instructions,
            'calories_burn_per_minute' => (float) $this->calories_burn_per_minute,
            'thumbnail_url' => $this->thumbnail_url,
            'video_url' => $this->video_url,
        ];
    }
}
