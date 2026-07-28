<?php

namespace Tests\Unit\Console\Commands;

use App\Models\LocationPing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeLocationPingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_purges_old_pings(): void
    {
        $user = User::factory()->create();

        LocationPing::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'recorded_at' => Carbon::now()->subDays(100),
        ]);

        LocationPing::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'recorded_at' => Carbon::now()->subDays(10),
        ]);

        $this->artisan('app:purge-location-pings')
            ->expectsOutputToContain('Purged 1 location pings')
            ->assertExitCode(0);

        $this->assertDatabaseCount('location_pings', 1);
    }

    public function test_command_respects_days_option(): void
    {
        $user = User::factory()->create();

        LocationPing::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'recorded_at' => Carbon::now()->subDays(50),
        ]);

        $this->artisan('app:purge-location-pings', ['--days' => 30])
            ->expectsOutputToContain('Purged 1 location pings')
            ->assertExitCode(0);

        $this->assertDatabaseCount('location_pings', 0);
    }
}
