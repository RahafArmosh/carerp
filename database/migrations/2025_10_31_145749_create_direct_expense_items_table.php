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
        Schema::create('direct_expense_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_expense_id');
            $table->unsignedBigInteger('sub_product_id');
            $table->decimal('amount', 18, 4);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('chart_account_id')->nullable();
            $table->timestamps();

            $table->index('direct_expense_id');
            $table->index('sub_product_id');
            $table->index('chart_account_id');
        });

        // Add foreign key constraint after direct_expenses table is ready
        // This will be done in a separate migration or manually after restructure
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_expense_items');
    }
};
