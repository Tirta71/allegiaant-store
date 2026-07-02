<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHOD_PAKASIR = 'pakasir';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
        'provider_reference',
        'provider_payload',
        'provider_fee',
        'provider_total',
        'provider_expires_at',
        'proof_url',
        'proof_uploaded_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'provider_fee' => 'integer',
            'provider_total' => 'integer',
            'provider_expires_at' => 'datetime',
            'proof_uploaded_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
