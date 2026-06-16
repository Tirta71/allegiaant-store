<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderPaymentConfirmationService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function confirm(Payment $payment, OrderPaymentConfirmationService $confirmation): RedirectResponse
    {
        $confirmation->confirm($payment->order, $payment, 'Payment dikonfirmasi admin.');

        return back()->with('status', 'Payment berhasil dikonfirmasi.');
    }
}
