<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'quantity' => \fake()->randomFloat(3, 1, 100),
            'unit_price' => \fake()->randomFloat(2, 10, 5000),
            'line_total' => \fake()->randomFloat(2, 100, 500000),
        ];
    }
}
