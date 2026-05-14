<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('brand_sub_brand');
    }

    public function down(): void
    {
        // Restored by re-running the original migration if needed.
    }
};
