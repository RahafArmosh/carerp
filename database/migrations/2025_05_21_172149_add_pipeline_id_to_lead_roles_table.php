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
        Schema::table('lead_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('pipeline_id')->nullable()->after('id');

        // If you have a pipelines table and want a foreign key constraint:
        $table->foreign('pipeline_id')->references('id')->on('pipelines')->onDelete('set null');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_roles', function (Blueprint $table) {
        $table->dropColumn('pipeline_id');

        // Or if using a foreign key:
        $table->dropForeign(['pipeline_id']);
        $table->dropColumn('pipeline_id');
    });
    }
};
