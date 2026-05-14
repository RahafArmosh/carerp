<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (!Schema::hasColumn('asns', 'boe_number')) {
                $table->string('boe_number')->nullable()->after('dec_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (Schema::hasColumn('asns', 'boe_number')) {
                $table->dropColumn('boe_number');
            }
        });
    }
};

