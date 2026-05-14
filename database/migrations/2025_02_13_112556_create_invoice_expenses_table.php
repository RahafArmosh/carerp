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
        Schema::create('invoice_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id'); // Define column before foreign key
            $table->foreign('account_id')->references('id')->on('chart_of_accounts')->onDelete('cascade');

            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_expenses', function (Blueprint $table) {
            //
        });
    }
};
