<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\TokenRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPaymentConfirmationService
{
    public function __construct(private readonly OrderReservationService $reservation)
    {
    }

    public function confirm(
        Order $order,
        ?Payment $payment = null,
        string $note = 'Order masuk antrean delivery pet.',
        bool $skipExpiration = false
    ): Order
    {
        if (! $skipExpiration) {
            $order = $this->reservation->expireIfNeeded($order);
        }

        return DB::transaction(function () use ($order, $payment, $note) {
            $order = Order::query()
                ->with(['items', 'payments'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->status === Order::STATUS_PAYMENT_CONFIRMED) {
                return $order->load(['items', 'payments', 'statusHistories']);
            }

            if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
                throw ValidationException::withMessages([
                    'order' => 'Order sudah tidak bisa dikonfirmasi.',
                ]);
            }

            foreach ($order->items as $item) {
                if ($item->item_type === OrderItem::TYPE_PET && $item->product_variant_id) {
                    $variant = ProductVariant::query()
                        ->with('product')
                        ->lockForUpdate()
                        ->findOrFail($item->product_variant_id);

                    $variant->increment('sales_count', $item->quantity);
                    $variant->product()->increment('sales_count', $item->quantity);
                }

                if ($item->item_type === OrderItem::TYPE_TOKEN && $item->token_rate_id) {
                    $rate = TokenRate::query()
                        ->with('product')
                        ->lockForUpdate()
                        ->findOrFail($item->token_rate_id);
                    $tokenAmount = (int) $item->token_amount_snapshot;

                    $rate->product()->increment('sales_count', $tokenAmount);
                }
            }

            $payment = $payment
                ? Payment::query()->lockForUpdate()->findOrFail($payment->id)
                : $order->payments()->where('method', Payment::METHOD_PAKASIR)->lockForUpdate()->first();

            if ($payment && $payment->order_id !== $order->id) {
                throw ValidationException::withMessages([
                    'payment' => 'Payment tidak cocok dengan order.',
                ]);
            }

            $payment ??= $order->payments()->create([
                'method' => Payment::METHOD_PAKASIR,
                'amount' => $order->total,
                'status' => Payment::STATUS_PENDING,
            ]);

            $payment->update([
                'status' => Payment::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            $order->update([
                'status' => Order::STATUS_PAYMENT_CONFIRMED,
                'status_note' => $note,
                'paid_at' => now(),
                'payment_expires_at' => null,
            ]);

            $order->statusHistories()->create([
                'status' => Order::STATUS_PAYMENT_CONFIRMED,
                'note' => $note,
            ]);

            return $order->load(['items', 'payments', 'statusHistories']);
        });
    }
}
