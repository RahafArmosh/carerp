<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            // Delivery location
            $table->string('delivery_location')->nullable();

            // Pick from location
            $table->enum('pick_from_location', ['ML', 'FZ'])
                  ->nullable();

            // Price group (can be enum or FK later)
            $table->unsignedBigInteger('price_group')
                  ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_location',
                'pick_from_location',
                'price_group',
            ]);
        });
    }
};
