<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OutOfStockRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutOfStockRequest>
 */
class OutOfStockRequestFactory extends Factory
{
    protected $model = OutOfStockRequest::class;

    public function definition(): array
    {
        return [];
    }
}
