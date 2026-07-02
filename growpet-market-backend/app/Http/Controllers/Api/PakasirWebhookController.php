<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderPaymentConfirmationService;
use App\Services\PakasirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PakasirWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PakasirService $pakasir,
        OrderPaymentConfirmationService $confirmation
    ): JsonResponse {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'order_id' => ['required', 'string', 'max:255'],
            'project' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $pakasir->ensureConfigured();

        $order = Order::query()
            ->byCode($data['order_id'])
            ->with(['items', 'payments'])
            ->firstOrFail();

        $pakasir->assertWebhookMatchesOrder($order, $data);

        if ($data['status'] !== 'completed') {
            return response()->json([
                'message' => 'Webhook diterima, status belum completed.',
            ]);
        }

        $transaction = $pakasir->transactionDetail($order);

        if (! $pakasir->isCompletedTransaction($transaction)) {
            throw ValidationException::withMessages([
                'payment' => 'Status Pakasir belum completed saat diverifikasi.',
            ]);
        }

        $pakasir->assertWebhookMatchesOrder($order, $transaction);

        $payment = DB::transaction(function () use ($order, $data): Payment {
            $payment = $order->payments()
                ->where('method', Payment::METHOD_PAKASIR)
                ->lockForUpdate()
                ->first();

            $payment ??= $order->payments()->create([
                'method' => Payment::METHOD_PAKASIR,
                'amount' => $order->total,
                'status' => Payment::STATUS_PENDING,
            ]);

            $payment->update([
                'amount' => $order->total,
                'provider_reference' => $data['order_id'],
            ]);

            return $payment;
        });

        $order = $confirmation->confirm(
            $order,
            $payment,
            'Payment Pakasir completed. Order masuk antrean delivery pet.',
            true
        );

        return response()->json([
            'message' => 'Payment Pakasir berhasil dikonfirmasi.',
            'data' => [
                'order_code' => $order->code,
                'status' => $order->status,
            ],
        ]);
    }
}
