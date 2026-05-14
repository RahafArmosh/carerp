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
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop old source column if exists
            if (Schema::hasColumn('campaigns', 'source')) {
                $table->dropColumn('source');
            }

            // Add foreign key instead
            $table->foreignId('source_id')->nullable()->constrained('sources')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign', function (Blueprint $table) {
            //
        });
    }
};
