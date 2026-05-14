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
        Schema::table('serial_numbers', function (Blueprint $table) {
            // Drop the foreign key constraint for grn_id (Laravel auto-generates constraint name)
            if (Schema::hasColumn('serial_numbers', 'grn_id')) {
                // Try to drop foreign key by column name
                $table->dropForeign(['grn_id']);
                // Drop the index if it exists
                $table->dropIndex(['grn_id']);
                // Drop the grn_id column
                $table->dropColumn('grn_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_numbers', function (Blueprint $table) {
            // Re-add grn_id column
            $table->unsignedBigInteger('grn_id')->after('id');
            
            // Re-add foreign key and index
            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('cascade');
            $table->index('grn_id');
        });
    }
};
