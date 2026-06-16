<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mutation_id')->constrained()->restrictOnDelete();
            $table->decimal('weight_kg', 8, 2);
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->string('sku')->nullable()->unique();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['product_id', 'mutation_id', 'weight_kg']);
            $table->index(['product_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
