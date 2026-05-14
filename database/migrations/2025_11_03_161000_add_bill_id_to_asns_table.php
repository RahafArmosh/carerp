<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (!Schema::hasColumn('asns', 'bill_id')) {
                $table->unsignedBigInteger('bill_id')->nullable()->after('status');
                $table->index('bill_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (Schema::hasColumn('asns', 'bill_id')) {
                $table->dropIndex(['bill_id']);
                $table->dropColumn('bill_id');
            }
        });
    }
};


