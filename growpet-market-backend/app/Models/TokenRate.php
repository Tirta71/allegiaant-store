<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenRate extends Model
{
    protected $fillable = [
        'product_id',
        'price_per_thousand',
        'min_nominal',
        'stock_token',
        'effective_from',
        'effective_until',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_thousand' => 'integer',
            'min_nominal' => 'integer',
            'stock_token' => 'integer',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function calculateTokenAmount(int $nominal): int
    {
        return (int) floor(($nominal / $this->price_per_thousand) * 1000);
    }
}
