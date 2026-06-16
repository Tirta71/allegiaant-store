<?php

use App\Models\Order;
use App\Services\OrderReservationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire-pending', function () {
    $reservation = app(OrderReservationService::class);
    $expiredCount = 0;

    Order::query()
        ->where('status', Order::STATUS_PENDING_PAYMENT)
        ->whereNotNull('payment_expires_at')
        ->where('payment_expires_at', '<=', now())
        ->chunkById(100, function ($orders) use ($reservation, &$expiredCount) {
            foreach ($orders as $order) {
                $reservation->cancel($order, 'Waktu payment 10 menit habis. Pesanan otomatis dibatalkan dan stok dikembalikan.');
                $expiredCount++;
            }
        });

    $this->info("Expired {$expiredCount} pending orders.");
})->purpose('Expire pending payment orders and release reserved stock');

Schedule::command('orders:expire-pending')->everyMinute();
