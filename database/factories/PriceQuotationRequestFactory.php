<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceQuotationRequestFactory extends Factory
{
    protected $model = PriceQuotationRequest::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'quantity_requested' => $this->faker->randomFloat(3, 1, 100),
            'status' => 'requested',
            'requested_at' => now(),
        ];
    }
}
