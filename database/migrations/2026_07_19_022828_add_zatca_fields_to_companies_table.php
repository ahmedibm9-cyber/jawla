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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('country', 2)->default('EG')->after('is_active');
            $table->boolean('zatca_enabled')->default(false)->after('country');
            $table->string('zatca_csid')->nullable()->after('zatca_enabled');
            $table->text('zatca_secret')->nullable()->after('zatca_csid');
            $table->string('zatca_environment')->nullable()->after('zatca_secret'); // sandbox, production
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['country', 'zatca_enabled', 'zatca_csid', 'zatca_secret', 'zatca_environment']);
        });
    }
};
