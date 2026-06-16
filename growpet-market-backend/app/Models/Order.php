<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'buyer_roblox_username',
        'buyer_whatsapp',
        'buyer_notes',
        'subtotal',
        'total',
        'total_items',
        'status',
        'status_note',
        'delivery_proof_url',
        'delivery_proof_uploaded_at',
        'delivery_proof_note',
        'paid_at',
        'payment_expires_at',
        'stock_reserved_at',
        'stock_released_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'total' => 'integer',
            'total_items' => 'integer',
            'delivery_proof_uploaded_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'stock_reserved_at' => 'datetime',
            'stock_released_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }
}
