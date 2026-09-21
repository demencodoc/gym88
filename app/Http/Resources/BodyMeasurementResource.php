<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BodyMeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'measured_at' => $this->measured_at?->format('Y-m-d'),
            'weight_kg' => (float) $this->weight_kg,
            'height_cm' => (float) $this->height_cm,
            'bmi' => (float) $this->bmi,
            'body_fat_percentage' => $this->body_fat_percentage ? (float) $this->body_fat_percentage : null,
            'muscle_mass_kg' => $this->muscle_mass_kg ? (float) $this->muscle_mass_kg : null,
            'chest_cm' => $this->chest_cm ? (float) $this->chest_cm : null,
            'waist_cm' => $this->waist_cm ? (float) $this->waist_cm : null,
            'hips_cm' => $this->hips_cm ? (float) $this->hips_cm : null,
            'arms_cm' => $this->arms_cm ? (float) $this->arms_cm : null,
            'thighs_cm' => $this->thighs_cm ? (float) $this->thighs_cm : null,
            'notes' => $this->notes,
            'photos' => [
                'front' => new MediaFileResource($this->whenLoaded('frontPhoto')),
                'side' => new MediaFileResource($this->whenLoaded('sidePhoto')),
                'back' => new MediaFileResource($this->whenLoaded('backPhoto')),
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
