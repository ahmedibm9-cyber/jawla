<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\LocationPing;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\LocationPingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationPingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_record_stores_ping_when_on_shift(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
            'ended_at' => null,
        ]);

        $ping = app(LocationPingService::class)->record($rep, 24.7136, 46.6753, 10.5);

        $this->assertNotNull($ping);
        $this->assertSame($company->id, $ping->company_id);
        $this->assertSame($rep->id, $ping->user_id);
        $this->assertEqualsWithDelta(24.7136, (float) $ping->latitude, 0.0001);
        $this->assertEqualsWithDelta(46.6753, (float) $ping->longitude, 0.0001);
        $this->assertEqualsWithDelta(10.5, (float) $ping->accuracy, 0.01);
    }

    public function test_record_returns_null_when_off_shift(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        // No work session

        $ping = app(LocationPingService::class)->record($rep, 24.7136, 46.6753);

        $this->assertNull($ping);
        $this->assertDatabaseCount('location_pings', 0);
    }

    public function test_record_deduplicates_within_15_seconds(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
            'ended_at' => null,
        ]);

        $service = app(LocationPingService::class);
        $first = $service->record($rep, 24.7136, 46.6753);
        $second = $service->record($rep, 24.7140, 46.6758);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertDatabaseCount('location_pings', 1);
    }

    public function test_latest_per_rep_returns_recent_pings(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id, 'name' => 'Ahmed']);
        $rep->assignRole('sales_rep');

        LocationPing::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'recorded_at' => now()->subMinutes(5),
        ]);

        $result = app(LocationPingService::class)->latestPerRep(30);

        $this->assertCount(1, $result);
        $this->assertSame('Ahmed', $result[0]['name']);
        $this->assertArrayHasKey('lat', $result[0]);
        $this->assertArrayHasKey('lng', $result[0]);
        $this->assertArrayHasKey('minutes_ago', $result[0]);
    }

    public function test_latest_per_rep_excludes_stale_pings(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        LocationPing::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'recorded_at' => now()->subMinutes(60),
        ]);

        $result = app(LocationPingService::class)->latestPerRep(30);

        $this->assertCount(0, $result);
    }
}
