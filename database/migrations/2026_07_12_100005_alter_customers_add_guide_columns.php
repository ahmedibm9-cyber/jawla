<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_group_id')->nullable()->after('route_id');
            $table->unsignedBigInteger('territory_id')->nullable()->after('customer_group_id');
            $table->unsignedBigInteger('price_list_id')->nullable()->after('territory_id');
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->onDelete('set null')->after('price_list_id');
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null')->after('is_active');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('added_by');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('status');
            $table->datetime('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->index('customer_group_id');
            $table->index('territory_id');
            $table->index('price_list_id');
            $table->index('account_manager_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index('name_ar');
            $table->index('name_en');
            $table->index('phone');
        });

        // Drop old unique on code (was global) and add (company_id, code) composite
        DB::statement('ALTER TABLE customers DROP CONSTRAINT IF EXISTS customers_code_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS customers_company_code_unique ON customers (company_id, code)');

        // Drop global unique on phone (guide says "unique within company")
        // phone column is nullable; add composite unique
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS customers_company_phone_unique ON customers (company_id, phone) WHERE phone IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customers_company_code_unique');
        DB::statement('DROP INDEX IF EXISTS customers_company_phone_unique');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['customer_group_id']);
            $table->dropIndex(['territory_id']);
            $table->dropIndex(['price_list_id']);
            $table->dropIndex(['account_manager_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['company_id', 'status']);
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['name_en']);
            $table->dropIndex(['phone']);
            $table->dropColumn([
                'customer_group_id', 'territory_id', 'price_list_id',
                'account_manager_id', 'added_by', 'status', 'approved_by',
                'approved_at', 'rejection_reason',
            ]);
            $table->string('code')->unique()->change();
        });
    }
};
