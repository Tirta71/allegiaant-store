<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderReservationService;
use App\Support\OrderPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentProofController extends Controller
{
    public function __invoke(
        Request $request,
        string $code,
        OrderPayload $payload,
        OrderReservationService $reservation
    ): JsonResponse
    {
        $data = $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        $order = Order::query()->byCode($code)->firstOrFail();
        $order = $reservation->expireIfNeeded($order);

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'payment' => 'Waktu payment sudah habis atau pesanan sudah tidak menunggu payment.',
            ]);
        }

        if ($order->payments()->where('method', Payment::METHOD_PAKASIR)->exists()) {
            throw ValidationException::withMessages([
                'payment' => 'Payment Pakasir tidak memakai upload bukti manual. Silakan bayar melalui link Pakasir.',
            ]);
        }

        $order = DB::transaction(function () use ($code, $data) {
            $order = Order::query()
                ->byCode($code)
                ->with(['items', 'payments'])
                ->lockForUpdate()
                ->firstOrFail();

            $path = $data['proof']->store("payment-proofs/{$order->code}", 'public');
            $proofUrl = Storage::disk('public')->url($path);

            $payment = $order->payments()->where('method', 'qris')->lockForUpdate()->first();

            $payment ??= $order->payments()->create([
                'method' => 'qris',
                'amount' => $order->total,
                'status' => Payment::STATUS_PENDING,
            ]);

            $payment->update([
                'amount' => $order->total,
                'status' => Payment::STATUS_PENDING,
                'proof_url' => $proofUrl,
                'proof_uploaded_at' => $payment->proof_uploaded_at ?: now(),
            ]);

            $note = 'Bukti payment sudah diupload. Menunggu konfirmasi admin.';

            $order->update([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'status_note' => $note,
                'payment_expires_at' => null,
            ]);

            $order->statusHistories()->create([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'note' => $note,
            ]);

            return $order->load(['items', 'payments', 'statusHistories']);
        });

        return response()->json([
            'data' => $payload->make($order),
        ]);
    }
}
