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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // Campaign name
            $table->date('start_date')->nullable();      // Optional start date
            $table->date('end_date')->nullable();        // Optional end date
            $table->enum('status', ['active', 'inactive'])->default('inactive'); // Status
            $table->string('target_country')->nullable();
            $table->string('source')->nullable();       // e.g., Google, Facebook, Email
            $table->string('url')->nullable();          // or ->text() if you expect very long URLs
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('assigned_to');   // FK to users table
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
