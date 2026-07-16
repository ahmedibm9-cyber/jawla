<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'sku' => fake()->unique()->numerify('SKU-#####'),
            'name_ar' => fake()->word(),
            'name_en' => fake()->word(),
            'packaging_type' => fake()->randomElement($packaging),
            'unit' => fake()->randomElement($units),
            'price' => fake()->randomFloat(2, 100, 5000),
            'cost' => fake()->randomFloat(2, 50, 3000),
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
