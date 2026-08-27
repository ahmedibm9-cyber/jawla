<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alarm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alarm>
 */
class AlarmFactory extends Factory
{
    protected $model = Alarm::class;

    public function definition(): array
    {
        return [];
    }
}
