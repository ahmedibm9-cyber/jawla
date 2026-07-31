<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'batch_number' => 'B-'.strtoupper(\Faker\Factory::create()->unique()->bothify('??##')),
            'manufacture_date' => now()->subMonths(2),
            'expiry_date' => now()->addYear(),
            'coa_file_path' => null,
            'supplier_id' => null,
            'received_date' => now()->subMonth(),
            'is_active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expiry_date' => now()->subDay()]);
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(fn () => ['expiry_date' => now()->addDays($days)]);
    }
}
