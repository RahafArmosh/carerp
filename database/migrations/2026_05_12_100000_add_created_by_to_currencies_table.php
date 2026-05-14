<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->default(0)->after('exchange_rate');
        });

        // Legacy rows become shared system currencies (created_by = 0).
        DB::table('currencies')->whereNull('created_by')->update(['created_by' => 0]);
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['created_by', 'code']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropUnique(['created_by', 'code']);
            $table->dropIndex(['created_by']);
            $table->unique('code');
            $table->dropColumn('created_by');
        });
    }
};
