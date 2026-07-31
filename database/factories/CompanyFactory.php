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
            'name_ar' => 'شركة '.\Faker\Factory::create()->company(),
            'name_en' => \Faker\Factory::create()->company(),
            'tax_number' => \Faker\Factory::create()->unique()->numerify('TAX-########'),
            'address' => \Faker\Factory::create()->address(),
            'phone' => \Faker\Factory::create()->phoneNumber(),
            'currency' => 'EGP',
            'vat_percent' => 14.00,
            'is_active' => true,
        ];
    }
}
