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
            'name_ar' => 'شركة '.$this->faker->company(),
            'name_en' => $this->faker->company(),
            'tax_number' => $this->faker->unique()->numerify('TAX-########'),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'currency' => 'EGP',
            'vat_percent' => 14.00,
            'is_active' => true,
        ];
    }
}
