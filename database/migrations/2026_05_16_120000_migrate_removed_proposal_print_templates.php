<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where('name', 'proposal_template')
            ->whereIn('value', ['template3', 'template4'])
            ->update(['value' => 'template11']);
    }

    public function down(): void
    {
        // Cannot restore which tenants used Rio vs London
    }
};
