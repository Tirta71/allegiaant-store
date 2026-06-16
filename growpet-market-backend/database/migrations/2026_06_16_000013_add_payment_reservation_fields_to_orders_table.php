<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            $table->timestamp('stock_reserved_at')->nullable()->after('payment_expires_at');
            $table->timestamp('stock_released_at')->nullable()->after('stock_reserved_at');
            $table->timestamp('cancelled_at')->nullable()->after('stock_released_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_expires_at',
                'stock_reserved_at',
                'stock_released_at',
                'cancelled_at',
            ]);
        });
    }
};
