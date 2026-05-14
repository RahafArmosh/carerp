<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sub_products')) {
            return;
        }

        if (! Schema::hasColumn('sub_products', 'product_no')) {
            return;
        }

        if (! Schema::hasColumn('sub_products', 'chassis_no')) {
            Schema::table('sub_products', function (Blueprint $table) {
                $table->string('chassis_no')->nullable();
            });
        }

        DB::table('sub_products')->select('id', 'product_no', 'chassis_no')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $ch = trim((string) ($row->chassis_no ?? ''));
                $pn = trim((string) ($row->product_no ?? ''));
                if ($ch === '' && $pn !== '') {
                    DB::table('sub_products')->where('id', $row->id)->update(['chassis_no' => $row->product_no]);
                }
            }
        });

        Schema::table('sub_products', function (Blueprint $table) {
            $table->dropColumn('product_no');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sub_products')) {
            return;
        }

        if (Schema::hasColumn('sub_products', 'product_no')) {
            return;
        }

        Schema::table('sub_products', function (Blueprint $table) {
            $table->string('product_no')->nullable();
        });

        DB::table('sub_products')->select('id', 'chassis_no')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $ch = trim((string) ($row->chassis_no ?? ''));
                if ($ch !== '') {
                    DB::table('sub_products')->where('id', $row->id)->update(['product_no' => $row->chassis_no]);
                }
            }
        });
    }
};
