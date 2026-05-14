<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meta Lead Ads / Graph API lead object id (payload key "id"), distinct from user_leads.lead_id FK.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'lead_id')) {
                $table->string('lead_id', 64)->nullable()->after('gclid');
                $table->unique('lead_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'lead_id')) {
                $table->dropUnique(['lead_id']);
                $table->dropColumn('lead_id');
            }
        });
    }
};
