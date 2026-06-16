<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('token_rates', 'stock_token')) {
                $table->unsignedBigInteger('stock_token')->default(0)->after('min_nominal');
            }
        });

        if (Schema::hasColumn('token_rates', 'max_nominal')) {
            Schema::table('token_rates', function (Blueprint $table) {
                $table->dropColumn('max_nominal');
            });
        }
    }

    public function down(): void
    {
        Schema::table('token_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('token_rates', 'max_nominal')) {
                $table->unsignedBigInteger('max_nominal')->nullable()->after('min_nominal');
            }
        });

        if (Schema::hasColumn('token_rates', 'stock_token')) {
            Schema::table('token_rates', function (Blueprint $table) {
                $table->dropColumn('stock_token');
            });
        }
    }
};
