<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CashBox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBox>
 */
class CashBoxFactory extends Factory
{
    protected $model = CashBox::class;

    public function definition(): array
    {
        return [];
    }
}
