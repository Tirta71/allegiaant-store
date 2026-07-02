<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('proof_uploaded_at')->nullable()->after('proof_url');
        });

        DB::table('payments')
            ->whereNotNull('proof_url')
            ->whereNull('proof_uploaded_at')
            ->update(['proof_uploaded_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('proof_uploaded_at');
        });
    }
};
