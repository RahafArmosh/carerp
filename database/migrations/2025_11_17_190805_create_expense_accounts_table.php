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
        Schema::create('expense_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('chart_account_id')->default('0');
            $table->decimal('price', 15, 2)->default('0.00');
            $table->string('description')->nullable();
            $table->string('type')->default('Expense');
            $table->integer('ref_id')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_accounts');
    }
};
