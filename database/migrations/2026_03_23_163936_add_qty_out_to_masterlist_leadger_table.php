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
        Schema::table('masterlist_leadger', function (Blueprint $table) {
            $table->decimal('qty_out', 16, 2)->default(0)->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('masterlist_leadger', function (Blueprint $table) {
            $table->dropColumn('qty_out');
        });
    }
};