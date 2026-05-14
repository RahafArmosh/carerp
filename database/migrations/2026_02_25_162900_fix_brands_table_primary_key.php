<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix brands.id so it is AUTO_INCREMENT and no longer causes duplicate '0' on insert.
     */
    public function up(): void
    {
        if (!Schema::hasTable('brands')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $maxId = (int) DB::table('brands')->max('id');
            while (DB::table('brands')->where('id', 0)->exists()) {
                $maxId++;
                DB::statement('UPDATE brands SET id = ? WHERE id = 0 LIMIT 1', [$maxId]);
                if (Schema::hasTable('brand_category')) {
                    DB::table('brand_category')->where('brand_id', 0)->update(['brand_id' => $maxId]);
                }
            }
            if ($maxId >= 0) {
                DB::statement('ALTER TABLE brands AUTO_INCREMENT = ' . ($maxId + 1));
            }

            DB::statement('ALTER TABLE brands MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migration (cannot fully restore previous broken state).
     */
    public function down(): void
    {
        // No safe down – table was already broken before this migration.
    }
};
