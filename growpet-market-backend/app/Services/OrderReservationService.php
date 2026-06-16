<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\TokenRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrderReservationService
{
    public function reserveItem(array $item): array
    {
        if ($item['type'] === OrderItem::TYPE_PET) {
            $variant = ProductVariant::query()
                ->available()
                ->with(['product', 'mutation'])
                ->lockForUpdate()
                ->findOrFail($item['product_variant_id']);
            $quantity = (int) ($item['quantity'] ?? 1);

            if ($quantity > $variant->stock) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$variant->product->name} {$variant->mutation->name} tidak cukup.",
                ]);
            }

            $variant->decrement('stock', $quantity);

            return [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'item_type' => OrderItem::TYPE_PET,
                'product_name_snapshot' => $variant->product->name,
                'mutation_name_snapshot' => $variant->mutation?->name,
                'weight_kg_snapshot' => $variant->weight_kg,
                'unit_price' => $variant->price,
                'quantity' => $quantity,
                'line_total' => $variant->price * $quantity,
            ];
        }

        $product = \App\Models\Product::query()
            ->tokens()
            ->active()
            ->findOrFail($item['product_id']);
        $rate = TokenRate::query()
            ->active()
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->findOrFail($item['token_rate_id']);
        $nominal = (int) $item['nominal'];

        if ($nominal < $rate->min_nominal) {
            throw ValidationException::withMessages([
                'items' => 'Minimal pembelian token Rp '.number_format($rate->min_nominal, 0, ',', '.').'.',
            ]);
        }

        $tokenAmount = $rate->calculateTokenAmount($nominal);

        if ($tokenAmount < 1) {
            throw ValidationException::withMessages([
                'items' => 'Nominal token terlalu kecil.',
            ]);
        }

        if ($tokenAmount > $rate->stock_token) {
            throw ValidationException::withMessages([
                'items' => 'Stok token tersisa '.number_format($rate->stock_token, 0, ',', '.').' token.',
            ]);
        }

        $rate->decrement('stock_token', $tokenAmount);

        return [
            'product_id' => $product->id,
            'token_rate_id' => $rate->id,
            'item_type' => OrderItem::TYPE_TOKEN,
            'product_name_snapshot' => $product->name,
            'token_amount_snapshot' => $tokenAmount,
            'token_rate_snapshot' => $rate->price_per_thousand,
            'package_label_snapshot' => number_format($tokenAmount, 0, ',', '.').' Token',
            'unit_price' => $nominal,
            'quantity' => 1,
            'line_total' => $nominal,
        ];
    }

    public function cancel(Order $order, string $note = 'Pesanan dibatalkan. Stok dikembalikan.'): Order
    {
        return DB::transaction(function () use ($order, $note) {
            $order = Order::query()
                ->with(['items', 'payments', 'statusHistories'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
                return $order;
            }

            $this->releaseReservedStock($order);

            $cancelledAt = now();

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'status_note' => $note,
                'cancelled_at' => $cancelledAt,
                'stock_released_at' => $cancelledAt,
            ]);

            $order->statusHistories()->create([
                'status' => Order::STATUS_CANCELLED,
                'note' => $note,
            ]);

            $order->load(['items', 'payments', 'statusHistories']);
            Storage::disk('public')->deleteDirectory("payment-proofs/{$order->code}");
            $order->delete();

            return $order;
        });
    }

    public function expireIfNeeded(Order $order): Order
    {
        if (
            $order->status === Order::STATUS_PENDING_PAYMENT
            && $order->payment_expires_at
            && now()->greaterThanOrEqualTo($order->payment_expires_at)
        ) {
            return $this->cancel($order, 'Waktu payment 10 menit habis. Pesanan otomatis dibatalkan dan stok dikembalikan.');
        }

        return $order;
    }

    private function releaseReservedStock(Order $order): void
    {
        if (! $order->stock_reserved_at || $order->stock_released_at) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->item_type === OrderItem::TYPE_PET && $item->product_variant_id) {
                ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->lockForUpdate()
                    ->increment('stock', $item->quantity);
            }

            if ($item->item_type === OrderItem::TYPE_TOKEN && $item->token_rate_id) {
                TokenRate::query()
                    ->whereKey($item->token_rate_id)
                    ->lockForUpdate()
                    ->increment('stock_token', (int) $item->token_amount_snapshot);
            }
        }
    }
}
