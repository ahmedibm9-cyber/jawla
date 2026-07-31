<?php

namespace Database\Factories;

use App\Models\LocationPing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationPing>
 */
class LocationPingFactory extends Factory
{
    protected $model = LocationPing::class;

    public function definition(): array
    {
        return [
            'latitude' => \Faker\Factory::create()->randomFloat(7, 24.5, 25.0),
            'longitude' => \Faker\Factory::create()->randomFloat(7, 46.5, 47.0),
            'accuracy' => \Faker\Factory::create()->randomFloat(2, 3, 40),
            'recorded_at' => now(),
        ];
    }
}
