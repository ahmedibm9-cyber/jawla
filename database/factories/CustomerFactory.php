<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'code' => \Faker\Factory::create()->unique()->numerify('CUST-####'),
            'name_ar' => \Faker\Factory::create()->company(),
            'name_en' => \Faker\Factory::create()->company(),
            'phone' => \Faker\Factory::create()->phoneNumber(),
            'address' => \Faker\Factory::create()->address(),
            'credit_limit' => 0,
            'balance' => 0,
            'is_active' => true,
            'status' => 'approved',
        ];
    }
}
