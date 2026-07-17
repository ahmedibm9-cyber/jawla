<?php

namespace Database\Factories;

use App\Enums\VisitPurpose;
use App\Enums\VisitStatus;
use App\Models\Customer;
use App\Models\Route;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'route_id' => Route::factory(),
            'purpose' => VisitPurpose::Sale,
            'status' => VisitStatus::Closed,
            'is_out_of_route' => false,
            'arrival_confirmed' => true,
            'arrival_confirmed_at' => now(),
            'checkin_at' => now()->subHour(),
            'checkout_at' => now(),
        ];
    }
}
