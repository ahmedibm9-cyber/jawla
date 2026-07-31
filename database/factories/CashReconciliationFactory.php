<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashReconciliationFactory extends Factory
{
    public function definition(): array
    {
        $expected = \Faker\Factory::create()->randomFloat(2, 100, 5000);
        $counted = $expected;

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'work_session_id' => null,
            'expected_amount' => $expected,
            'counted_amount' => $counted,
            'variance' => $counted - $expected,
            'notes' => null,
            'status' => 'pending',
        ];
    }

    public function withVariance(float $delta): static
    {
        return $this->state(fn (array $attrs) => [
            'counted_amount' => $attrs['expected_amount'] + $delta,
            'variance' => $delta,
        ]);
    }
}
