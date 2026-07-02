<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderPaymentConfirmationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function confirm(Payment $payment, OrderPaymentConfirmationService $confirmation): RedirectResponse
    {
        $confirmation->confirm($payment->order, $payment, 'Payment dikonfirmasi admin.');

        return back()->with('status', 'Payment berhasil dikonfirmasi.');
    }

    public function resetProof(Payment $payment): RedirectResponse
    {
        $oldProofUrl = null;

        DB::transaction(function () use ($payment, &$oldProofUrl): void {
            $payment = Payment::query()
                ->with('order')
                ->lockForUpdate()
                ->findOrFail($payment->id);
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($payment->order_id);

            if ($order->status !== Order::STATUS_PENDING_PAYMENT || $payment->status === Payment::STATUS_CONFIRMED) {
                throw ValidationException::withMessages([
                    'payment' => 'Bukti payment hanya bisa direset untuk order pending payment.',
                ]);
            }

            if ($payment->method === Payment::METHOD_PAKASIR) {
                throw ValidationException::withMessages([
                    'payment' => 'Payment Pakasir tidak memakai reset bukti manual.',
                ]);
            }

            if (! $payment->proof_url) {
                throw ValidationException::withMessages([
                    'payment' => 'Belum ada bukti payment yang bisa direset.',
                ]);
            }

            $oldProofUrl = $payment->proof_url;
            $note = 'Bukti payment belum valid. Silakan upload ulang bukti payment.';

            $payment->update([
                'status' => Payment::STATUS_PENDING,
                'proof_url' => null,
                'confirmed_at' => null,
            ]);

            $order->update([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'status_note' => $note,
                'payment_expires_at' => now()->addMinutes(10),
            ]);

            $order->statusHistories()->create([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'note' => $note,
            ]);
        });

        $this->deleteProofFile($oldProofUrl);

        return back()->with('status', 'Bukti payment direset. Buyer bisa upload ulang dalam 10 menit.');
    }

    private function deleteProofFile(?string $proofUrl): void
    {
        if (! $proofUrl) {
            return;
        }

        $path = parse_url($proofUrl, PHP_URL_PATH) ?: $proofUrl;
        $storagePrefix = '/storage/';

        if (! str_starts_with($path, $storagePrefix)) {
            return;
        }

        Storage::disk('public')->delete(substr($path, strlen($storagePrefix)));
    }
}
