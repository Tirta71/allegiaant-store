<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->text('provider_payload')->nullable()->after('provider_reference');
            $table->unsignedBigInteger('provider_fee')->nullable()->after('amount');
            $table->unsignedBigInteger('provider_total')->nullable()->after('provider_fee');
            $table->timestamp('provider_expires_at')->nullable()->after('provider_payload');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'provider_payload',
                'provider_fee',
                'provider_total',
                'provider_expires_at',
            ]);
        });
    }
};
