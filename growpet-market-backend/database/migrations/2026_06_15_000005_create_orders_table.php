<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('buyer_roblox_username');
            $table->string('buyer_whatsapp');
            $table->text('buyer_notes')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->string('status', 40)->default('pending_payment')->index();
            $table->text('status_note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
