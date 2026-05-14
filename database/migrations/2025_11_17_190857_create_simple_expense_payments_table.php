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
        Schema::create('simple_expense_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('expense_id');
            $table->date('date');
            $table->decimal('amount', 16, 2)->default('0.0');
            $table->integer('account_id');
            $table->integer('payment_method')->default(0);
            $table->integer('payment_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('add_receipt')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->decimal('currency_rate', 10, 2)->nullable();
            $table->decimal('amount_in_currency', 16, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simple_expense_payments');
    }
};
