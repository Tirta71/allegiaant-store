<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('token_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type', 20)->index();
            $table->string('product_name_snapshot');
            $table->string('mutation_name_snapshot')->nullable();
            $table->decimal('weight_kg_snapshot', 8, 2)->nullable();
            $table->unsignedBigInteger('token_amount_snapshot')->nullable();
            $table->unsignedBigInteger('token_rate_snapshot')->nullable();
            $table->string('package_label_snapshot')->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('line_total');
            $table->timestamps();

            $table->index(['order_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
