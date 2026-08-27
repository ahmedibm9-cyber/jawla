<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SyncReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncReceipt>
 */
class SyncReceiptFactory extends Factory
{
    protected $model = SyncReceipt::class;

    public function definition(): array
    {
        return [];
    }
}
