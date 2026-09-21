<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'amount' => (float) $this->amount,
            'amount_formatted' => number_format((float) $this->amount, 0, ',', '.').' VNĐ',
            'type' => $this->type,
            'method' => $this->method,
            'gateway' => $this->gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'status' => $this->status,
            'description' => $this->description,
            'paid_at' => $this->paid_at?->toISOString(),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
