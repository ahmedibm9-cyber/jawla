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
            'name_ar' => 'شركة '.fake()->company(),
            'name_en' => fake()->company(),
            'tax_number' => fake()->unique()->numerify('TAX-########'),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'currency' => 'EGP',
            'vat_percent' => 14.00,
            'is_active' => true,
        ];
    }
}
