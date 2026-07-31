<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name_ar' => $this->faker->city(),
            'name_en' => $this->faker->city(),
            'region' => $this->faker->city(),
            'is_active' => true,
        ];
    }
}
