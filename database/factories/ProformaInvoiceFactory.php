<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ProformaInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProformaInvoiceFactory extends Factory
{
    protected $model = ProformaInvoice::class;

    public function definition(): array
    {
        $subtotal = 1000.00;
        $vat = 140.00;

        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'proforma_number' => 'PF-'.Str::random(5),
            'subtotal' => $subtotal,
            'vat_amount' => $vat,
            'total' => $subtotal + $vat,
            'status' => 'sent',
            'posting_date' => now(),
        ];
    }
}
