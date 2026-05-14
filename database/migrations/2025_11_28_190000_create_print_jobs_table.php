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
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type')->default('pos_receipt'); // pos_receipt, invoice, etc.
            $table->string('reference_id')->nullable(); // POS ID, Invoice ID, etc.
            $table->unsignedBigInteger('user_id')->nullable(); // User who initiated the print
            $table->string('printer_ip')->nullable(); // Printer IP address
            $table->string('printer_port')->default('9100'); // Printer port
            $table->text('print_data'); // JSON data for the print job
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable(); // Error message if failed
            $table->integer('attempts')->default(0); // Number of retry attempts
            $table->timestamp('processed_at')->nullable(); // When the job was processed
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('status');
            $table->index('job_type');
            $table->index('user_id');
            $table->index('created_at');
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};

