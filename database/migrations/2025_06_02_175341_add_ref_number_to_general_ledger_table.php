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
        Schema::table('general_ledger', function (Blueprint $table) {
            $table->string('ref_number')->nullable()->after('ref_id'); // Adjust position if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_ledger', function (Blueprint $table) {
            $table->dropColumn('ref_number');
        });
    }
};
