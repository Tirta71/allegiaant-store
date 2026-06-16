<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderCancellationController;
use App\Http\Controllers\Api\PaymentProofController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\TokenRateController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product:slug}', [ProductController::class, 'show']);
Route::get('products/{product:slug}/variants', [ProductVariantController::class, 'index']);
Route::get('token-products/{product:slug}/rate', [TokenRateController::class, 'show']);

Route::post('orders', [OrderController::class, 'store']);
Route::get('orders/{code}', [OrderController::class, 'show']);
Route::post('orders/{code}/cancel', OrderCancellationController::class);
Route::get('orders/{code}/status-history', [OrderController::class, 'statusHistory']);
Route::post('orders/{code}/payment-proof', PaymentProofController::class);
