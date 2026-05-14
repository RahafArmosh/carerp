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
        DB::statement("ALTER TABLE lead_role_conditions 
        MODIFY COLUMN operation ENUM('=', '!=', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty', '>', '<') 
        NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE lead_role_conditions 
        MODIFY COLUMN operation ENUM('=', '!=', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty') 
        NOT NULL");
    }
};
