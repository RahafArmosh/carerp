<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->text('message')->nullable()->after('subject');
            $table->string('source')->nullable()->after('message');
            $table->string('source_url')->nullable()->after('source');
        });
    }

    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['message', 'source', 'source_url']);
        });
    }
};
