<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room' => $this->room,
            'current_bookings_count' => $this->current_bookings_count,
            'spots_remaining' => $this->whenLoaded('gymClass', function () {
                return max(0, $this->gymClass->max_capacity - $this->current_bookings_count);
            }),
            'trainer' => new TrainerResource($this->whenLoaded('trainer')),
            'gym_class' => new GymClassResource($this->whenLoaded('gymClass')),
        ];
    }
}
