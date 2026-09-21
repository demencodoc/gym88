<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'membership_code' => $this->membership_code,
            'qr_token' => $this->qr_token,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'price_paid' => (float) $this->price_paid,
            'status' => $this->status,
            'days_remaining' => $this->days_remaining,
            'remaining_pt_sessions' => $this->remaining_pt_sessions,
            'suspended_at' => $this->suspended_at?->format('Y-m-d'),
            'suspended_until' => $this->suspended_until?->format('Y-m-d'),
            'package' => new MembershipPackageResource($this->whenLoaded('package')),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
