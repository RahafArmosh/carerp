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
        // Migrate existing category_id data to pivot table
        // Only migrate records where category_id is not null and not -1
        $customFields = DB::table('custom_fields')
            ->whereNotNull('category_id')
            ->where('category_id', '!=', -1)
            ->get();

        foreach ($customFields as $customField) {
            // Check if the category exists
            $categoryExists = DB::table('product_service_categories')
                ->where('id', $customField->category_id)
                ->exists();

            if ($categoryExists) {
                // Insert into pivot table, avoiding duplicates
                DB::table('custom_field_category')->insertOrIgnore([
                    'custom_field_id' => $customField->id,
                    'product_service_category_id' => $customField->category_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't need to be reversed as we're migrating data forward
        // The remove_category_id migration will handle the rollback
    }
};
