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
        Schema::create('warehouse_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique(); // Unique request number
            $table->integer('from_warehouse')->default(0);
            $table->integer('to_warehouse')->default(0);
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->date('request_date')->nullable();
            $table->text('notes')->nullable();
            $table->integer('created_by')->default('0');
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('from_warehouse');
            $table->index('to_warehouse');
            $table->index('created_by');
            $table->index('request_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_requests');
    }
};
