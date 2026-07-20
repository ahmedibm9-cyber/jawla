<?php

namespace Tests\Feature;

use App\Filament\Pages\RepLiveMap;
use App\Livewire\App\LocationTracker;
use App\Models\Company;
use App\Models\LocationPing;
use App\Models\Route;
use App\Models\User;
use App\Models\WorkSession;
use App\Services\LocationPingService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CG3 live rep tracking: on-shift-only ping recording, dedupe, coordinate
 * validation, and the manager live-map query (latest per rep, company scoped).
 */
class LocationTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = $this->makeUser('rep');
        $this->manager = $this->makeUser('sales_manager');
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function makeUser(string $role): User
    {
        $u = User::factory()->create(['company_id' => $this->company->id]);
        $u->assignRole($role);

        return $u;
    }

    private function openShift(User $rep): WorkSession
    {
        $route = Route::factory()->create(['company_id' => $this->company->id]);

        return WorkSession::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $rep->id,
            'route_id' => $route->id,
            'started_at' => now()->subHour(),
            'ended_at' => null,
        ]);
    }

    public function test_records_a_ping_while_on_shift(): void
    {
        $session = $this->openShift($this->rep);

        $ping = app(LocationPingService::class)->record($this->rep, 24.7136, 46.6753, 12.5);

        $this->assertNotNull($ping);
        $this->assertDatabaseHas('location_pings', [
            'user_id' => $this->rep->id,
            'company_id' => $this->company->id,
            'work_session_id' => $session->id,
        ]);
    }

    public function test_does_not_record_when_off_shift(): void
    {
        // No open work session for the rep.
        $ping = app(LocationPingService::class)->record($this->rep, 24.7, 46.7, 10);

        $this->assertNull($ping);
        $this->assertDatabaseCount('location_pings', 0);
    }

    public function test_dedupes_rapid_pings(): void
    {
        $this->openShift($this->rep);
        $svc = app(LocationPingService::class);

        $first = $svc->record($this->rep, 24.7, 46.7, 10);
        $second = $svc->record($this->rep, 24.71, 46.71, 10);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertDatabaseCount('location_pings', 1);
    }

    public function test_livewire_beacon_records_for_on_shift_rep(): void
    {
        $this->openShift($this->rep);

        Livewire::actingAs($this->rep)
            ->test(LocationTracker::class)
            ->call('recordPing', 24.7136, 46.6753, 8.0);

        $this->assertDatabaseHas('location_pings', ['user_id' => $this->rep->id]);
    }

    public function test_livewire_beacon_rejects_invalid_coordinates(): void
    {
        $this->openShift($this->rep);

        Livewire::actingAs($this->rep)
            ->test(LocationTracker::class)
            ->call('recordPing', 200.0, 999.0, 8.0);

        $this->assertDatabaseCount('location_pings', 0);
    }

    public function test_manager_can_access_live_map_rep_cannot(): void
    {
        $this->actingAs($this->manager);
        $this->assertTrue(RepLiveMap::canAccess());

        $this->actingAs($this->rep);
        $this->assertFalse(RepLiveMap::canAccess());
    }

    public function test_live_map_page_renders(): void
    {
        app(ActiveCompanyContext::class)->setFromUser($this->manager);

        Livewire::actingAs($this->manager)
            ->test(RepLiveMap::class)
            ->assertOk()
            ->call('broadcastPoints')
            ->assertDispatched('pings-updated');
    }

    public function test_beacon_component_renders(): void
    {
        Livewire::actingAs($this->rep)
            ->test(LocationTracker::class)
            ->assertOk();
    }

    public function test_map_returns_latest_ping_per_rep_company_scoped(): void
    {
        // Two pings for our rep — the later one should win.
        LocationPing::factory()->create([
            'company_id' => $this->company->id, 'user_id' => $this->rep->id,
            'latitude' => 24.70, 'longitude' => 46.70, 'recorded_at' => now()->subMinutes(10),
        ]);
        LocationPing::factory()->create([
            'company_id' => $this->company->id, 'user_id' => $this->rep->id,
            'latitude' => 24.80, 'longitude' => 46.80, 'recorded_at' => now()->subMinutes(2),
        ]);

        // A ping in another company must not leak.
        $other = Company::factory()->create();
        $otherRep = User::factory()->create(['company_id' => $other->id]);
        LocationPing::factory()->create([
            'company_id' => $other->id, 'user_id' => $otherRep->id, 'recorded_at' => now(),
        ]);

        app(ActiveCompanyContext::class)->setFromUser($this->manager);

        $points = app(LocationPingService::class)->latestPerRep(30);

        $this->assertCount(1, $points);
        $this->assertSame($this->rep->name, $points[0]['name']);
        $this->assertEqualsWithDelta(24.80, $points[0]['lat'], 0.0001);
    }
}
