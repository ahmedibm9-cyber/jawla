<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = 1000.00;
        $vat = 140.00;

        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'invoice_number' => 'INV-'.Str::random(5),
            'status' => InvoiceStatus::Submitted,
            'subtotal' => $subtotal,
            'vat_amount' => $vat,
            'total' => $subtotal + $vat,
            'paid_amount' => 0,
            'remaining_amount' => $subtotal + $vat,
            'issued_at' => now(),
        ];
    }
}
