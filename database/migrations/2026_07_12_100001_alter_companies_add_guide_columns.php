<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('legal_entity')->nullable()->after('name_en');
            $table->string('parent_company')->default('Global Plastic Company (GPC)')->after('legal_entity');
            $table->string('abbr')->nullable()->after('parent_company');
            $table->string('commercial_registration_number')->nullable()->after('tax_number');
            $table->string('bank_name')->nullable()->after('vat_percent');
            $table->string('bank_account')->nullable()->after('bank_name');
            $table->string('bank_iban')->nullable()->after('bank_account');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_entity', 'parent_company', 'abbr',
                'commercial_registration_number', 'bank_name', 'bank_account', 'bank_iban',
            ]);
        });
    }
};