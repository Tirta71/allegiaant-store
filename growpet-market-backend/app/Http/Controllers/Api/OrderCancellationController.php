<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderReservationService;
use App\Support\OrderPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderCancellationController extends Controller
{
    public function __invoke(
        Request $request,
        string $code,
        OrderReservationService $reservation,
        OrderPayload $payload
    ): JsonResponse {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $order = Order::query()->byCode($code)->with(['items', 'payments'])->firstOrFail();

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json([
                'data' => $payload->make($order),
            ]);
        }

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan ini sudah tidak bisa dibatalkan.',
            ]);
        }

        if (! $order->payment_expires_at) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan sudah mengupload bukti payment dan menunggu pengecekan admin.',
            ]);
        }

        if (now()->greaterThanOrEqualTo($order->payment_expires_at)) {
            $order = $reservation->expireIfNeeded($order);

            return response()->json([
                'data' => $payload->make($order),
            ]);
        }

        $note = $data['reason'] ?? 'Pesanan dibatalkan buyer. Stok dikembalikan.';
        $order = $reservation->cancel($order, $note);

        return response()->json([
            'data' => $payload->make($order),
        ]);
    }
}
