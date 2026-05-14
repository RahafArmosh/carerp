<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('taxes', 'type')) {
            Schema::table('taxes', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->enum('type', ['add', 'subtract'])->default('add');
        });
    }
};
