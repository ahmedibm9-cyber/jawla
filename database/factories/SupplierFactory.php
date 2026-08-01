<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'code' => 'SUP-'.Str::random(4),
            'name_ar' => 'مورد اختبار',
            'name_en' => 'Test Supplier',
            'type' => 'local',
            'is_active' => true,
        ];
    }
}
