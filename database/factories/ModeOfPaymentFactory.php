<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ModeOfPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeOfPayment>
 */
class ModeOfPaymentFactory extends Factory
{
    protected $model = ModeOfPayment::class;

    public function definition(): array
    {
        return [];
    }
}
