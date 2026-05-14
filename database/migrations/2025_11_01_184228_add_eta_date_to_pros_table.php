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
        Schema::table('pros', function (Blueprint $table) {
            $table->date('eta_date')->nullable()->after('supplier_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pros', function (Blueprint $table) {
            $table->dropColumn('eta_date');
        });
    }
};
