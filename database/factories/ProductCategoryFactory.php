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
            'name_ar' => 'فئة اختبار',
            'name_en' => 'Test Category',
            'sort_order' => 1,
        ];
    }
}
