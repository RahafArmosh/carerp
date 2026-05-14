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
            // Drop old global unique index on pro_no.
            $table->dropUnique('pros_pro_no_unique');

            // Enforce uniqueness per company/creator.
            $table->unique(['created_by', 'pro_no'], 'pros_created_by_pro_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pros', function (Blueprint $table) {
            $table->dropUnique('pros_created_by_pro_no_unique');
            $table->unique('pro_no', 'pros_pro_no_unique');
        });
    }
};

