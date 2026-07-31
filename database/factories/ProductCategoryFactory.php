<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name_ar' => \Faker\Factory::create()->word(),
            'name_en' => \Faker\Factory::create()->word(),
            'sort_order' => \Faker\Factory::create()->numberBetween(1, 100),
        ];
    }
}
