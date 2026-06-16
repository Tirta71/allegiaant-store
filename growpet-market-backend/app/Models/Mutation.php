<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mutation extends Model
{
    protected $fillable = [
        'name',
        'price_modifier',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price_modifier' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
