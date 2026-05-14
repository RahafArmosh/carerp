<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'currency_rate')) {
                if (Schema::hasColumn('journal_entries', 'currency_id')) {
                    $table->decimal('currency_rate', 18, 6)->nullable()->after('currency_id');
                } else {
                    $table->decimal('currency_rate', 18, 6)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('currency_rate');
        });
    }
};
