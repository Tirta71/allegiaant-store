<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;

class TestimonialController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $orders = Order::query()
            ->with('items')
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('delivery_proof_url')
            ->latest('delivery_proof_uploaded_at')
            ->limit(9)
            ->get();

        return response()->json([
            'data' => $orders->map(fn (Order $order) => [
                'id' => $order->id,
                'roblox_username' => $order->buyer_roblox_username,
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'type' => $item->item_type,
                    'name' => $item->product_name_snapshot,
                    'mutation' => $item->mutation_name_snapshot,
                    'weight_kg' => $item->weight_kg_snapshot ? (float) $item->weight_kg_snapshot : null,
                    'package_label' => $item->package_label_snapshot,
                    'token_amount' => $item->token_amount_snapshot,
                    'quantity' => $item->quantity,
                ])->values(),
                'delivery_proof' => [
                    'url' => $order->delivery_proof_url,
                    'uploaded_at' => $order->delivery_proof_uploaded_at?->toISOString(),
                ],
            ])->values(),
        ]);
    }
}
