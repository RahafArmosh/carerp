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
        // Make product_service_id nullable for backward compatibility
        if (Schema::hasColumn('combo_offers', 'product_service_id')) {
            Schema::table('combo_offers', function (Blueprint $table) {
                $table->unsignedBigInteger('product_service_id')->nullable()->change();
            });
        }

        // Add brand_id and sub_brand_id if they don't exist
        if (!Schema::hasColumn('combo_offers', 'brand_id')) {
            Schema::table('combo_offers', function (Blueprint $table) {
                $table->unsignedBigInteger('brand_id')->nullable()->after('warehouse_id');
            });
        }
        
        if (!Schema::hasColumn('combo_offers', 'sub_brand_id')) {
            Schema::table('combo_offers', function (Blueprint $table) {
                $table->unsignedBigInteger('sub_brand_id')->nullable()->after('brand_id');
            });
        }

        // Add foreign keys if they don't exist (using raw SQL to check)
        $foreignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'combo_offers' AND COLUMN_NAME = 'brand_id' AND CONSTRAINT_NAME != 'PRIMARY'");
        if (empty($foreignKeys)) {
            Schema::table('combo_offers', function (Blueprint $table) {
                $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            });
        }

        $foreignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'combo_offers' AND COLUMN_NAME = 'sub_brand_id' AND CONSTRAINT_NAME != 'PRIMARY'");
        if (empty($foreignKeys)) {
            Schema::table('combo_offers', function (Blueprint $table) {
                $table->foreign('sub_brand_id')->references('id')->on('sub_brands')->onDelete('set null');
            });
        }

        // Create pivot table for many-to-many relationship with products
        if (!Schema::hasTable('combo_offer_product_service')) {
            Schema::create('combo_offer_product_service', function (Blueprint $table) {
                $table->id();
                $table->foreignId('combo_offer_id')->constrained('combo_offers')->onDelete('cascade');
                $table->foreignId('product_service_id')->constrained('product_services')->onDelete('cascade');
                $table->timestamps();
                
                // Ensure unique combination with shorter name for MySQL compatibility
                $table->unique(['combo_offer_id', 'product_service_id'], 'combo_offer_product_unique');
            });
        } else {
            // Table exists - check if unique constraint exists, if not add it
            $indexes = \DB::select("SHOW INDEXES FROM combo_offer_product_service WHERE Key_name = 'combo_offer_product_unique'");
            if (empty($indexes)) {
                Schema::table('combo_offer_product_service', function (Blueprint $table) {
                    $table->unique(['combo_offer_id', 'product_service_id'], 'combo_offer_product_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop pivot table
        Schema::dropIfExists('combo_offer_product_service');

        // Remove brand_id and sub_brand_id
        Schema::table('combo_offers', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['sub_brand_id']);
            $table->dropColumn(['brand_id', 'sub_brand_id']);
        });

        // Revert product_service_id to not nullable (if needed)
        Schema::table('combo_offers', function (Blueprint $table) {
            $table->unsignedBigInteger('product_service_id')->nullable(false)->change();
        });
    }
};
