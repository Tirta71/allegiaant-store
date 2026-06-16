<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price_per_thousand');
            $table->unsignedBigInteger('min_nominal')->default(1);
            $table->unsignedBigInteger('stock_token')->default(0);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['product_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_rates');
    }
};
