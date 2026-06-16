<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'mutation_id',
        'weight_kg',
        'price',
        'stock',
        'sku',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'price' => 'integer',
            'stock' => 'integer',
            'sales_count' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->where('stock', '>', 0);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(Mutation::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
