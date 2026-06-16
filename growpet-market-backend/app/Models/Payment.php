<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
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
        'proof_url',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
