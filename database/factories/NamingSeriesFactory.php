<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NamingSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NamingSeries>
 */
class NamingSeriesFactory extends Factory
{
    protected $model = NamingSeries::class;

    public function definition(): array
    {
        return [];
    }
}
