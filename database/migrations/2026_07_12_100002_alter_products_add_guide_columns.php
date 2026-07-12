<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->enum('packaging_type', ['bag', 'jumbo_bag', 'barrel', 'drum', 'tank', 'iso_tank', 'other'])->default('bag')->after('unit');
            $table->boolean('track_batch')->default(false)->after('vat_applicable');
            $table->boolean('track_expiry')->default(false)->after('track_batch');
            $table->boolean('has_variants')->default(false)->after('track_expiry');
            $table->foreignId('variant_of')->nullable()->constrained('products')->onDelete('set null')->after('has_variants');
            $table->boolean('is_bundle')->default(false)->after('variant_of');
            $table->decimal('max_discount', 5, 2)->nullable()->after('is_bundle');
            $table->enum('valuation_method', ['fifo', 'moving_average', 'standard'])->default('moving_average')->after('max_discount');

            $table->index('variant_of');
            $table->index('sku');
            $table->index('name_ar');
            $table->index('name_en');
            $table->index(['company_id', 'is_active']);
        });

        // Change unit enum to add 'ton'
        DB::statement("ALTER TABLE products ALTER COLUMN unit TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_unit_check");
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_unit_check CHECK (unit::text = ANY (ARRAY['ton'::text, 'kg'::text, 'piece'::text, 'box'::text, 'carton'::text]))");
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'packaging_type', 'track_batch', 'track_expiry',
                'has_variants', 'variant_of', 'is_bundle', 'max_discount', 'valuation_method',
            ]);
            $table->dropIndex(['sku']);
            $table->dropIndex(['name_ar']);
            $table->dropIndex(['name_en']);
            $table->dropIndex(['company_id', 'is_active']);
        });

        // Restore old enum
        DB::statement("ALTER TABLE products DROP CONSTRAINT IF EXISTS products_unit_check");
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_unit_check CHECK (unit::text = ANY (ARRAY['piece'::text, 'box'::text, 'carton'::text, 'kg'::text, 'liter'::text, 'gallon'::text]))");
    }
};