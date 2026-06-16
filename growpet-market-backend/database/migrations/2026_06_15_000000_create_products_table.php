<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type', 20)->index();
            $table->string('name');
            $table->string('rarity')->nullable()->index();
            $table->unsignedInteger('sales_count')->default(0);
            $table->boolean('featured')->default(false)->index();
            $table->boolean('best_seller')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
