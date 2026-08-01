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
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'accuracy' => 10.00,
            'recorded_at' => now(),
        ];
    }
}
