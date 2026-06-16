<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('delivery_proof_url')->nullable()->after('status_note');
            $table->timestamp('delivery_proof_uploaded_at')->nullable()->after('delivery_proof_url');
            $table->text('delivery_proof_note')->nullable()->after('delivery_proof_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_proof_url',
                'delivery_proof_uploaded_at',
                'delivery_proof_note',
            ]);
        });
    }
};
