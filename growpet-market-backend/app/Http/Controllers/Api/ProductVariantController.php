<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        abort_unless($product->active && $product->type === Product::TYPE_PET, 404);

        $variants = $product->availableVariants()
            ->with('mutation')
            ->orderBy('weight_kg')
            ->get()
            ->sortBy([['mutation.name', 'asc'], ['weight_kg', 'asc']])
            ->values()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'mutation_id' => $variant->mutation_id,
                'mutation' => $variant->mutation?->name,
                'weight_kg' => (float) $variant->weight_kg,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'sku' => $variant->sku,
            ]);

        return response()->json(['data' => $variants]);
    }
}
