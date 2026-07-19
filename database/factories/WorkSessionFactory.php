<?php

namespace Database\Factories;

use App\Models\Route;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkSessionFactory extends Factory
{
    protected $model = WorkSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'route_id' => Route::factory(),
            'started_at' => now()->subHours(2),
            'ended_at' => now(),
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ];
    }
}
