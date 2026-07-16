<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cash_box_variance');
    }

    public function down(): void
    {
        // Re-creating cash_box_variance is not needed — it was an unauthorized table.
    }
};
