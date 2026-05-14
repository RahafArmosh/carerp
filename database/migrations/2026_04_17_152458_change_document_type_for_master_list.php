<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE masterlist_leadger 
            MODIFY document_type ENUM(
                'ASN',
                'GRN',
                'SO',
                'INVOICE',
                'PRO',
                'BILL'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE masterlist_leadger 
            MODIFY document_type ENUM(
                'ASN',
                'GRN',
                'SO',
                'INVOICE',
                'PRO'
            ) NOT NULL
        ");
    }
};