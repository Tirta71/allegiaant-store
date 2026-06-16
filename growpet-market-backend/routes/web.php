<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MutationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\TokenRateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::post('/logout', [AuthController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('products', ProductController::class)->except(['show']);
        Route::resource('mutations', MutationController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('product-variants', ProductVariantController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('token-rates', TokenRateController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{order}/delivery-proof', [OrderController::class, 'uploadDeliveryProof'])->name('orders.delivery-proof');
        Route::patch('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    });
});
