<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'customer_id' => \App\Models\Customer::factory(),
            'user_id' => \App\Models\User::factory(),
            'invoice_id' => null,
            'visit_id' => null,
            'mode_of_payment_id' => null,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'method' => fake()->randomElement(['cash', 'cheque', 'transfer', 'other']),
            'exchange_rate' => 1.000000,
            'base_amount' => fake()->randomFloat(2, 100, 10000),
            'collected_at' => now(),
            'posting_date' => today(),
            'notes' => fake()->optional()->sentence(),
            'cancelled_at' => null,
            'cancelled_by' => null,
        ];
    }
}