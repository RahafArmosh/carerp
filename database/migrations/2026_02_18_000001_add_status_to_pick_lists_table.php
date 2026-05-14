<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Picking list status: 1-draft, 2-under picking, 3-partially picked, 4-picking completed.
     */
    public function up(): void
    {
        Schema::table('pick_lists', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('assigned_to')
                ->comment('draft, under_picking, partially_picked, picking_completed');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pick_lists', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
