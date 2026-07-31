<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name_ar' => $this->faker->word(),
            'name_en' => $this->faker->word(),
            'type' => 'main',
            'user_id' => null,
            'is_active' => true,
        ];
    }
}
