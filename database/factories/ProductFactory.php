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
            'sku' => $this->faker->unique()->numerify('SKU-#####'),
            'name_ar' => $this->faker->word(),
            'name_en' => $this->faker->word(),
            'packaging_type' => $this->faker->randomElement($packaging),
            'unit' => $this->faker->randomElement($units),
            'price' => $this->faker->randomFloat(2, 100, 5000),
            'cost' => $this->faker->randomFloat(2, 50, 3000),
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
