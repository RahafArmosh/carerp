<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('direct_expenses', 'attachment')) {
                $table->string('attachment')->nullable()->after('exchange_rate');
            }
        });

        Schema::table('simple_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('simple_expenses', 'attachment')) {
                $table->string('attachment')->nullable()->after('exchange_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('direct_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('direct_expenses', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });

        Schema::table('simple_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('simple_expenses', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
};
