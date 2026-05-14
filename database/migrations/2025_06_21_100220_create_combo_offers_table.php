<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComboOffersTable extends Migration
{
    public function up()
    {
        Schema::create('combo_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')
                  ->constrained('warehouses')
                  ->onDelete('cascade');
            $table->foreignId('product_service_id')
                  ->constrained('product_services')
                  ->onDelete('cascade');
            $table->enum('type', ['bogo', 'tiered_pricing']);
            $table->unsignedInteger('buy_quantity')->nullable();
            $table->unsignedInteger('get_quantity')->nullable();
            $table->json('tiered_prices')->nullable();
            $table->date('valid_until')->nullable(); 
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('combo_offers');
    }
}

