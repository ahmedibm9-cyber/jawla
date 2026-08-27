<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReturnRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnRecord>
 */
class ReturnRecordFactory extends Factory
{
    protected $model = ReturnRecord::class;

    public function definition(): array
    {
        return [];
    }
}
