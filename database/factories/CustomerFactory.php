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
            'code' => $this->faker->unique()->numerify('CUST-####'),
            'name_ar' => $this->faker->company(),
            'name_en' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'credit_limit' => 0,
            'balance' => 0,
            'is_active' => true,
            'status' => 'approved',
        ];
    }
}
