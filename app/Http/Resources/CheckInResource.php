<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'subscription_id' => $this->subscription_id,
            'check_in_time' => $this->check_in_time?->toISOString(),
            'check_out_time' => $this->check_out_time?->toISOString(),
            'method' => $this->method,
            'notes' => $this->notes,
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
        ];
    }
}
