<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'avatar_url' => $this->avatar_url,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'rating_avg' => $this->rating_avg,
            'total_clients' => $this->total_clients,
            'pt_cert_url' => $this->pt_cert_url,
            'availabilities' => TrainerAvailabilityResource::collection($this->whenLoaded('availabilities')),
        ];
    }
}
