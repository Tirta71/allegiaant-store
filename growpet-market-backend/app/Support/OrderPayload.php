<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\PakasirService;

class OrderPayload
{
    public function __construct(private readonly PakasirService $pakasir)
    {
    }

    public function make(Order $order): array
    {
        $order->loadMissing(['items', 'payments']);
        $pakasirPayment = $order->payments
            ->where('method', Payment::METHOD_PAKASIR)
            ->sortByDesc('created_at')
            ->first();

        return [
            'id' => $order->id,
            'code' => $order->code,
            'buyer' => [
                'roblox_username' => $order->buyer_roblox_username,
                'whatsapp' => $order->buyer_whatsapp,
                'notes' => $order->buyer_notes,
            ],
            'summary' => [
                'subtotal' => $order->subtotal,
                'total' => $order->total,
                'total_items' => $order->total_items,
            ],
            'status' => $order->status,
            'status_note' => $order->status_note,
            'delivery_proof' => [
                'url' => $order->delivery_proof_url,
                'uploaded_at' => $order->delivery_proof_uploaded_at?->toISOString(),
                'note' => $order->delivery_proof_note,
            ],
            'paid_at' => $order->paid_at?->toISOString(),
            'payment_expires_at' => $order->payment_expires_at?->toISOString(),
            'payment_seconds_remaining' => $this->paymentSecondsRemaining($order),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
            'can_cancel' => $order->status === Order::STATUS_PENDING_PAYMENT
                && $order->payment_expires_at
                && $this->paymentSecondsRemaining($order) > 0,
            'created_at' => $order->created_at?->toISOString(),
            'payment_instructions' => [
                'method' => Payment::METHOD_PAKASIR,
                'amount' => $pakasirPayment?->provider_total ?: $order->total,
                'order_amount' => $order->total,
                'fee' => $pakasirPayment?->provider_fee,
                'total_payment' => $pakasirPayment?->provider_total ?: $order->total,
                'merchant_name' => config('payment.qris.merchant_name'),
                'qris_payload' => $pakasirPayment?->provider_payload,
                'expired_at' => $pakasirPayment?->provider_expires_at?->toISOString(),
                'payment_url' => $this->pakasir->paymentUrl($order),
                'qris_only' => (bool) config('payment.pakasir.qris_only', true),
            ],
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'type' => $item->item_type,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'token_rate_id' => $item->token_rate_id,
                'product_name' => $item->product_name_snapshot,
                'mutation_name' => $item->mutation_name_snapshot,
                'weight_kg' => $item->weight_kg_snapshot ? (float) $item->weight_kg_snapshot : null,
                'token_amount' => $item->token_amount_snapshot,
                'token_rate' => $item->token_rate_snapshot,
                'package_label' => $item->package_label_snapshot,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->values(),
            'payments' => $order->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'method' => $payment->method,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'provider_reference' => $payment->provider_reference,
                'provider_payload' => $payment->provider_payload,
                'provider_fee' => $payment->provider_fee,
                'provider_total' => $payment->provider_total,
                'provider_expires_at' => $payment->provider_expires_at?->toISOString(),
                'proof_url' => $payment->proof_url,
                'proof_uploaded_at' => $payment->proof_uploaded_at?->toISOString(),
                'confirmed_at' => $payment->confirmed_at?->toISOString(),
            ])->values(),
        ];
    }

    private function paymentSecondsRemaining(Order $order): int
    {
        if (! $order->payment_expires_at || $order->status !== Order::STATUS_PENDING_PAYMENT) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($order->payment_expires_at, false));
    }
}
