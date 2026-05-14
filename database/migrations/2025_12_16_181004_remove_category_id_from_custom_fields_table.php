<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['category_id']);
            // Then drop the column
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            // Add category_id column back
            $table->unsignedBigInteger('category_id')->nullable()->after('module');
            
            // Restore data from pivot table (take first category if multiple exist)
            $pivotData = DB::table('custom_field_category')
                ->select('custom_field_id', 'product_service_category_id')
                ->get()
                ->groupBy('custom_field_id');

            foreach ($pivotData as $customFieldId => $categories) {
                // Take the first category for each custom field
                $firstCategory = $categories->first();
                DB::table('custom_fields')
                    ->where('id', $customFieldId)
                    ->update(['category_id' => $firstCategory->product_service_category_id]);
            }

            // Add foreign key constraint
            $table->foreign('category_id')
                  ->references('id')
                  ->on('product_service_categories')
                  ->onDelete('cascade');
        });
    }
};
