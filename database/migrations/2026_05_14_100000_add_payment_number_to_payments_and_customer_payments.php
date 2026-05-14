<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_number')->nullable()->after('id');
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_number')->nullable()->after('id');
        });

        $this->backfillPaymentNumbers('payments');
        $this->backfillPaymentNumbers('customer_payments');
    }

    protected function backfillPaymentNumbers(string $table): void
    {
        $creatorIds = DB::table($table)->distinct()->pluck('created_by');

        foreach ($creatorIds as $createdBy) {
            $q = DB::table($table)->orderBy('id');
            if ($createdBy === null) {
                $q->whereNull('created_by');
            } else {
                $q->where('created_by', $createdBy);
            }
            $ids = $q->pluck('id');

            $n = 1;
            foreach ($ids as $id) {
                DB::table($table)->where('id', $id)->update(['payment_number' => $n]);
                $n++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_number');
        });

        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropColumn('payment_number');
        });
    }
};
