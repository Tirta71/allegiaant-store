<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class TokenRateController extends Controller
{
    public function show(Product $product): JsonResponse
    {
        abort_unless($product->active && $product->type === Product::TYPE_TOKEN, 404);

        $rate = $product->activeTokenRate;
        abort_unless($rate, 404);

        return response()->json([
            'data' => [
                'id' => $rate->id,
                'product_id' => $product->id,
                'product_slug' => $product->slug,
                'price_per_thousand' => $rate->price_per_thousand,
                'min_nominal' => $rate->min_nominal,
                'stock_token' => $rate->stock_token,
            ],
        ]);
    }
}
