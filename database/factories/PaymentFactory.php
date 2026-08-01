<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
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
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'invoice_id' => null,
            'visit_id' => null,
            'mode_of_payment_id' => null,
            'amount' => 1000.00,
            'method' => 'cash',
            'exchange_rate' => 1.000000,
            'base_amount' => 1000.00,
            'collected_at' => now(),
            'posting_date' => today(),
            'notes' => null,
            'cancelled_at' => null,
            'cancelled_by' => null,
        ];
    }
}
