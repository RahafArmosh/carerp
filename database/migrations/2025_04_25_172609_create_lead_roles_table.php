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
        Schema::create('lead_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Role name
            // $table->string('lead_column'); // The column to check in the lead
            // $table->enum('operation', ['=', 'contains']); // Type of comparison
            // $table->string('value'); // Value to compare
            $table->unsignedBigInteger('assigned_user_id'); // User to assign lead to
            $table->timestamps();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_roles');
    }
};
