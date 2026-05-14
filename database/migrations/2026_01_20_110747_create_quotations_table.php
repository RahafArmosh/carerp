<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->string('quotation_no')->unique();
            $table->date('quotation_date');

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('taxes')
                ->nullOnDelete();

            $table->enum('discount_type', ['percent', 'value'])->nullable();
            $table->decimal('discount_value', 16, 6)->nullable();

            $table->decimal('subtotal', 16, 6)->default(0);
            $table->decimal('tax_amount', 16, 6)->default(0);
            $table->decimal('total', 16, 6)->default(0);

            $table->unsignedBigInteger('created_by');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
