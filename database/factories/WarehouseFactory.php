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
            'name_ar' => \Faker\Factory::create()->word(),
            'name_en' => \Faker\Factory::create()->word(),
            'type' => 'main',
            'user_id' => null,
            'is_active' => true,
        ];
    }
}
