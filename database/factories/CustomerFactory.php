<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'route_id' => Route::factory(),
            'code' => 'CUST-'.Str::random(4),
            'name_ar' => 'عميل اختبار',
            'name_en' => 'Test Customer',
            'phone' => fake()->unique()->numerify('010000000##'),
            'address' => 'Test Address',
            'credit_limit' => 0,
            'balance' => 0,
            'is_active' => true,
            'status' => 'approved',
        ];
    }
}
