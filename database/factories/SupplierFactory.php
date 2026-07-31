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
            'code' => $this->faker->unique()->numerify('SUP-####'),
            'name_ar' => $this->faker->company(),
            'name_en' => $this->faker->company(),
            'type' => $this->faker->randomElement(['local', 'international']),
            'is_active' => true,
        ];
    }
}
