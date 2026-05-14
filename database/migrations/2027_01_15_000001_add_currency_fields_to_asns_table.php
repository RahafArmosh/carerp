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
        Schema::table('asns', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('asn_date');
            $table->decimal('exchange_rate', 16, 6)->default(1.0)->after('currency_id');
            
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->index('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asns', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropIndex(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate']);
        });
    }
};

