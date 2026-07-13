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
            'name_ar' => fake()->city(),
            'name_en' => fake()->city(),
            'region' => fake()->city(),
            'is_active' => true,
        ];
    }
}