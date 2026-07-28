<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\LocationPing;
use App\Models\User;
use App\Services\LocationPurgeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationPurgeServiceTest extends TestCase
{
    use RefreshDatabase;

    private LocationPurgeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocationPurgeService;
    }

    public function test_purges_old_pings(): void
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

        $deleted = $this->service->purge(90);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseCount('location_pings', 1);
    }

    public function test_purges_in_chunks(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 2500; $i++) {
            LocationPing::factory()->create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'recorded_at' => Carbon::now()->subDays(100),
            ]);
        }

        $deleted = $this->service->purge(90);

        $this->assertSame(2500, $deleted);
        $this->assertDatabaseCount('location_pings', 0);
    }

    public function test_no_pings_to_purge(): void
    {
        $deleted = $this->service->purge(90);
        $this->assertSame(0, $deleted);
    }

    public function test_custom_retention_period(): void
    {
        $user = User::factory()->create();

        LocationPing::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'recorded_at' => Carbon::now()->subDays(50),
        ]);

        $deleted = $this->service->purge(30);

        $this->assertSame(1, $deleted);
    }

    public function test_scoped_per_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        // Both companies have old pings
        LocationPing::factory()->create([
            'company_id' => $companyA->id,
            'user_id' => $userA->id,
            'recorded_at' => Carbon::now()->subDays(100),
        ]);
        LocationPing::factory()->create([
            'company_id' => $companyB->id,
            'user_id' => $userB->id,
            'recorded_at' => Carbon::now()->subDays(100),
        ]);

        $deleted = $this->service->purge(90);

        $this->assertSame(2, $deleted);
        $this->assertDatabaseCount('location_pings', 0);
    }

    public function test_invalid_retention_falls_back_to_default(): void
    {
        $user = User::factory()->create();

        LocationPing::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'recorded_at' => Carbon::now()->subDays(100),
        ]);

        // Zero retention should fall back to 90 days, still purges the 100-day ping
        $deleted = $this->service->purge(0);

        $this->assertSame(1, $deleted);
    }
}
