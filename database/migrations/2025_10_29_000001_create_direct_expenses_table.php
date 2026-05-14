<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('direct_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('sub_product_id');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('sub_product_id');
            $table->index('chart_account_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_expenses');
    }
};


