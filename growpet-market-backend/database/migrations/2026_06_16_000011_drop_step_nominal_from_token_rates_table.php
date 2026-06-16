<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('token_rates', 'step_nominal')) {
            Schema::table('token_rates', function (Blueprint $table) {
                $table->dropColumn('step_nominal');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('token_rates', 'step_nominal')) {
            Schema::table('token_rates', function (Blueprint $table) {
                $table->unsignedBigInteger('step_nominal')->default(1)->after('max_nominal');
            });
        }
    }
};
