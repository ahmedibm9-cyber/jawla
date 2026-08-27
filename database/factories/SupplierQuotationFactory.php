<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SupplierQuotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierQuotation>
 */
class SupplierQuotationFactory extends Factory
{
    protected $model = SupplierQuotation::class;

    public function definition(): array
    {
        return [];
    }
}
