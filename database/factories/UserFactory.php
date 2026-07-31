<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => \fake()->uuid(),
            'company_id' => Company::factory(),
            'name' => \fake()->name(),
            'email' => \fake()->unique()->safeEmail(),
            'phone' => \fake()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'employee_code' => \fake()->unique()->numerify('EMP-####'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
