<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->active()
            ->with(['activeTokenRate', 'availableVariants.mutation'])
            ->where(function ($query) {
                $query->where('products.type', Product::TYPE_TOKEN)
                    ->orWhereHas('availableVariants');
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('rarity'), fn ($query) => $query->where('rarity', $request->string('rarity')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('rarity', 'like', $search);
                });
            })
            ->when($request->string('sort')->toString() === 'name', fn ($query) => $query->orderBy('name'))
            ->when($request->string('sort')->toString() !== 'name', fn ($query) => $query->orderByDesc('sales_count'))
            ->get();

        return response()->json([
            'data' => $products->map(fn (Product $product) => $this->productPayload($product))->values(),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless($product->active, 404);
        abort_if($product->type === Product::TYPE_PET && ! $product->availableVariants()->exists(), 404);

        $product->load(['activeTokenRate', 'availableVariants.mutation']);

        return response()->json([
            'data' => $this->productPayload($product, includeVariants: true),
        ]);
    }

    private function productPayload(Product $product, bool $includeVariants = false): array
    {
        $variants = $product->availableVariants;
        $weights = $variants->pluck('weight_kg')->map(fn ($weight) => (float) $weight)->unique()->sort()->values();
        $mutations = $variants->pluck('mutation.name')->filter()->unique()->values();
        $startingPrice = $product->type === Product::TYPE_PET
            ? $variants->min('price')
            : $product->activeTokenRate?->price_per_thousand;

        $payload = [
            'id' => $product->id,
            'slug' => $product->slug,
            'type' => $product->type,
            'name' => $product->name,
            'image_url' => $product->image_url,
            'rarity' => $product->rarity,
            'starting_price' => $startingPrice ? (int) $startingPrice : null,
            'sales_count' => $product->sales_count,
            'total_stock' => $product->type === Product::TYPE_PET ? (int) $variants->sum('stock') : null,
            'featured' => $product->featured,
            'best_seller' => $product->best_seller,
            'mutations' => $mutations,
            'weights' => $weights,
            'token_rate' => $product->activeTokenRate ? [
                'id' => $product->activeTokenRate->id,
                'price_per_thousand' => $product->activeTokenRate->price_per_thousand,
                'min_nominal' => $product->activeTokenRate->min_nominal,
                'stock_token' => $product->activeTokenRate->stock_token,
            ] : null,
        ];

        if ($includeVariants) {
            $payload['variants'] = $variants
                ->sortBy([['mutation.name', 'asc'], ['weight_kg', 'asc']])
                ->map(fn ($variant) => [
                    'id' => $variant->id,
                    'mutation' => $variant->mutation?->name,
                    'mutation_id' => $variant->mutation_id,
                    'weight_kg' => (float) $variant->weight_kg,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'sku' => $variant->sku,
                ])
                ->values();
        }

        return $payload;
    }
}
