<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompanyBankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyBankAccount>
 */
class CompanyBankAccountFactory extends Factory
{
    protected $model = CompanyBankAccount::class;

    public function definition(): array
    {
        return [];
    }
}
