<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (!Schema::hasColumn('asns', 'hs_code')) {
                $table->string('hs_code')->nullable()->after('dec_date');
            }
            if (!Schema::hasColumn('asns', 'origin')) {
                $table->string('origin')->nullable()->after('hs_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            if (Schema::hasColumn('asns', 'origin')) {
                $table->dropColumn('origin');
            }
            if (Schema::hasColumn('asns', 'hs_code')) {
                $table->dropColumn('hs_code');
            }
        });
    }
};

