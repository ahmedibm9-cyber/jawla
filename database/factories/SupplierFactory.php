<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'code' => \Faker\Factory::create()->unique()->numerify('SUP-####'),
            'name_ar' => \Faker\Factory::create()->company(),
            'name_en' => \Faker\Factory::create()->company(),
            'type' => \Faker\Factory::create()->randomElement(['local', 'international']),
            'is_active' => true,
        ];
    }
}
