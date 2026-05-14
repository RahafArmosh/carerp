<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->mediumText('payload')->after('queue');
            // $table->tinyInteger('attempts')->default(0)->after('payload');
            $table->unsignedInteger('reserved_at')->nullable()->after('attempts');
            $table->unsignedInteger('available_at')->after('reserved_at');
            // $table->unsignedInteger('created_at')->after('available_at');
        });
    }

    public function down()
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['payload', 'attempts', 'reserved_at', 'available_at', 'created_at']);
        });
    }
};
