<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_formatted' => number_format((float) $this->price, 0, ',', '.').' VNĐ',
            'duration_days' => $this->duration_days,
            'has_pt' => $this->has_pt,
            'pt_sessions' => $this->pt_sessions,
            'benefits' => $this->benefits,
            'is_active' => $this->is_active,
        ];
    }
}
