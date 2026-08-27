<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GoodsInTransit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsInTransit>
 */
class GoodsInTransitFactory extends Factory
{
    protected $model = GoodsInTransit::class;

    public function definition(): array
    {
        return [];
    }
}
