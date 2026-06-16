<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    public const TYPE_PET = 'pet';

    public const TYPE_TOKEN = 'token';

    protected $fillable = [
        'slug',
        'type',
        'name',
        'image_url',
        'rarity',
        'featured',
        'best_seller',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'sales_count' => 'integer',
            'featured' => 'boolean',
            'best_seller' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopePets(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PET);
    }

    public function scopeTokens(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_TOKEN);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('active', true);
    }

    public function availableVariants(): HasMany
    {
        return $this->variants()->available();
    }

    public function tokenRates(): HasMany
    {
        return $this->hasMany(TokenRate::class);
    }

    public function activeTokenRate(): HasOne
    {
        return $this->hasOne(TokenRate::class)->where('active', true)->latestOfMany();
    }
}
