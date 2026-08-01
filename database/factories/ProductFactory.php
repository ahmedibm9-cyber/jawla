<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $units = ['ton', 'kg', 'piece', 'box', 'carton'];
        $packaging = ['bag', 'jumbo_bag', 'barrel', 'drum', 'tank'];

        return [
            'company_id' => Company::factory(),
            'category_id' => ProductCategory::factory(),
            'sku' => 'SKU-'.Str::random(5),
            'name_ar' => 'منتج اختبار',
            'name_en' => 'Test Product',
            'packaging_type' => $packaging[0],
            'unit' => $units[0],
            'price' => 500.00,
            'cost' => 300.00,
            'vat_applicable' => true,
            'track_batch' => false,
            'track_expiry' => false,
            'has_variants' => false,
            'is_bundle' => false,
            'valuation_method' => 'moving_average',
            'is_active' => true,
        ];
    }
}
