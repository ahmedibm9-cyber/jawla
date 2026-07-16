<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sync_queue');
    }

    public function down(): void
    {
        // Re-creating sync_queue is not needed — it was an unauthorized table.
    }
};
