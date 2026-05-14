<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->default(0)->after('name');
            $table->index('created_by');
        });

        // Legacy rows: attach to first super admin (id 1) so they remain visible to that tenant.
        DB::table('countries')->where('created_by', 0)->update(['created_by' => 1]);
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
