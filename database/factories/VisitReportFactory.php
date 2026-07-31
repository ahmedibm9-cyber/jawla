<?php

namespace Database\Factories;

use App\Models\Visit;
use App\Models\VisitReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitReportFactory extends Factory
{
    protected $model = VisitReport::class;

    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'summary' => \Faker\Factory::create()->sentence(),
            'submitted_at' => now(),
        ];
    }
}
