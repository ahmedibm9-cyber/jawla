<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('van_transfer_items', function (Blueprint $table): void {
            $table->decimal('received_quantity', 12, 3)->nullable()->after('quantity');
            $table->decimal('exception_quantity', 12, 3)->nullable()->after('received_quantity');
            $table->string('exception_reason')->nullable()->after('exception_quantity');
            $table->datetime('exceptioned_at')->nullable()->after('exception_reason');
        });
    }

    public function down(): void
    {
        Schema::table('van_transfer_items', function (Blueprint $table): void {
            $table->dropColumn(['received_quantity', 'exception_quantity', 'exception_reason', 'exceptioned_at']);
        });
    }
};
