<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'booked_at' => $this->booked_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'schedule' => new ClassScheduleResource($this->whenLoaded('schedule')),
        ];
    }
}
