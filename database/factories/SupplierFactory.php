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
            'code' => \fake()->unique()->numerify('SUP-####'),
            'name_ar' => \fake()->company(),
            'name_en' => \fake()->company(),
            'type' => \fake()->randomElement(['local', 'international']),
            'is_active' => true,
        ];
    }
}
