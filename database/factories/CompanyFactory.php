<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name_ar' => 'شركة اختبار',
            'name_en' => 'Test Company',
            'tax_number' => fake()->unique()->numerify('TAX-########'),
            'address' => 'Test Address, Cairo',
            'phone' => '01000000000',
            'currency' => 'EGP',
            'vat_percent' => 14.00,
            'is_active' => true,
        ];
    }
}
