<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DailyVisitAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyVisitAssignment>
 */
class DailyVisitAssignmentFactory extends Factory
{
    protected $model = DailyVisitAssignment::class;

    public function definition(): array
    {
        return [];
    }
}
