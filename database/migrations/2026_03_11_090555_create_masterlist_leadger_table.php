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
        Schema::create('masterlist_leadger', function (Blueprint $table) {
           $table->id();

            $table->foreignId('product_service_id')->constrained('product_services');
            $table->foreignId('warehouse_id')->constrained('warehouses');

            $table->decimal('qty', 16, 2);

            $table->enum('movement_type', [
                'free',
                'booked',
                'sold',
                'on_order'
            ]);

            // $table->string('document_type'); 
            // ASN, GRN, SO, INVOICE, PRO
            $table->enum('document_type', [
                'ASN',
                'GRN',
                'SO',
                'INVOICE',
                'PRO'
            ]);

            $table->integer('created_by')->default('0');
            $table->unsignedBigInteger('document_id'); 
            // id of the related document
            $table->index('created_by');
            $table->timestamps();

            $table->unique([
                'product_service_id',
                'warehouse_id',
                'document_id',
                'document_type',
                'movement_type'
            ], 'PWDDMT');
                    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('masterlist_leadger');
    }
};
