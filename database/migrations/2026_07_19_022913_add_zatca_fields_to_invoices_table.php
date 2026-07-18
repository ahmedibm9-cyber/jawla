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
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('hash_chain')->nullable()->after('uuid'); // Previous invoice hash for chain
            $table->text('cryptographic_stamp')->nullable()->after('hash_chain'); // Base64 encoded cryptographic stamp
            $table->string('zatca_status')->default('pending')->after('cryptographic_stamp'); // pending, submitted, cleared, rejected
            $table->timestamp('zatca_submitted_at')->nullable()->after('zatca_status');
            $table->text('zatca_response')->nullable()->after('zatca_submitted_at'); // Store ZATCA response
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'hash_chain', 'cryptographic_stamp', 'zatca_status', 'zatca_submitted_at', 'zatca_response']);
        });
    }
};
