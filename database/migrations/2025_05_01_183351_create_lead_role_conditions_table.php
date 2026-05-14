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
        Schema::create('lead_role_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_role_id')->constrained('lead_roles')->onDelete('cascade');
            $table->string('lead_column'); // Field to check
            $table->enum('operation', ['=', '!=', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty']);
            $table->string('value')->nullable(); // May be null for is_empty
            $table->enum('connector', ['AND', 'OR'])->nullable(); // Connector with next condition
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_role_conditions');
    }
};
