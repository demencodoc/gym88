<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_code',
        'reference_id',
        'reference_type',
        'amount',
        'currency',
        'payment_method',
        'status',
        'payment_gateway_ref',
        'signature_hash',
        'webhook_payload',
        'webhook_verified_at',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'webhook_payload' => 'array',
            'webhook_verified_at' => 'datetime',
            'transaction_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
