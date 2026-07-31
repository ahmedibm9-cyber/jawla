<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ProformaInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProformaInvoiceFactory extends Factory
{
    protected $model = ProformaInvoice::class;

    public function definition(): array
    {
        $subtotal = \Faker\Factory::create()->randomFloat(2, 100, 10000);
        $vat = \Faker\Factory::create()->randomFloat(2, 10, 1000);

        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'proforma_number' => \Faker\Factory::create()->unique()->numerify('PF-#####'),
            'subtotal' => $subtotal,
            'vat_amount' => $vat,
            'total' => $subtotal + $vat,
            'status' => 'sent',
            'posting_date' => now(),
        ];
    }
}
