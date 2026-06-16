<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public const TYPE_PET = 'pet';

    public const TYPE_TOKEN = 'token';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'token_rate_id',
        'item_type',
        'product_name_snapshot',
        'mutation_name_snapshot',
        'weight_kg_snapshot',
        'token_amount_snapshot',
        'token_rate_snapshot',
        'package_label_snapshot',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg_snapshot' => 'decimal:2',
            'token_amount_snapshot' => 'integer',
            'token_rate_snapshot' => 'integer',
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function tokenRate(): BelongsTo
    {
        return $this->belongsTo(TokenRate::class);
    }
}
