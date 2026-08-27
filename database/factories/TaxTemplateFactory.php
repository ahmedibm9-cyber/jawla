<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaxTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxTemplate>
 */
class TaxTemplateFactory extends Factory
{
    protected $model = TaxTemplate::class;

    public function definition(): array
    {
        return [];
    }
}
