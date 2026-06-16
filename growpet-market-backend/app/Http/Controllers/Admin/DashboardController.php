<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TokenRate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::query()->count(),
                'pets' => Product::query()->pets()->count(),
                'tokens' => Product::query()->tokens()->count(),
                'variants' => ProductVariant::query()->count(),
                'tokenRates' => TokenRate::query()->count(),
                'orders' => Order::query()->count(),
                'pendingOrders' => Order::query()->where('status', Order::STATUS_PENDING_PAYMENT)->count(),
            ],
            'latestOrders' => Order::query()->latest()->limit(8)->get(),
        ]);
    }
}
