<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_employees', 'note')) {
                $table->text('note')->nullable()->after('locationOut');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_employees', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_employees', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
