<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 30)->default('qris');
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('pending')->index();
            $table->string('provider_reference')->nullable();
            $table->text('proof_url')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
