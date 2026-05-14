<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing unique constraint on asn_no
        // The constraint name might be 'asns_asn_no_unique' or 'asn_no'
        try {
            DB::statement('ALTER TABLE asns DROP INDEX asns_asn_no_unique');
        } catch (\Exception $e) {
            // Try alternative constraint name
            try {
                DB::statement('ALTER TABLE asns DROP INDEX asn_no');
            } catch (\Exception $e2) {
                // If constraint doesn't exist, continue
                \Log::warning('Could not drop existing asn_no unique constraint: ' . $e2->getMessage());
            }
        }

        // Add composite unique constraint on (asn_no, created_by)
        // This allows the same ASN number for different creators
        DB::statement('ALTER TABLE asns ADD UNIQUE KEY asns_asn_no_created_by_unique (asn_no, created_by)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the composite unique constraint
        try {
            DB::statement('ALTER TABLE asns DROP INDEX asns_asn_no_created_by_unique');
        } catch (\Exception $e) {
            \Log::warning('Could not drop composite unique constraint: ' . $e->getMessage());
        }

        // Restore the original global unique constraint
        Schema::table('asns', function (Blueprint $table) {
            $table->unique('asn_no');
        });
    }
};
