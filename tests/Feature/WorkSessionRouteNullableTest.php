<?php

namespace Tests\Feature;

use App\Livewire\App\Home;
use App\Models\Company;
use App\Models\User;
use App\Models\WorkSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * JAWLA-LARAVEL13-1: route_id must be nullable — reps start work before
 * they are assigned to a specific route.
 */
class WorkSessionRouteNullableTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');
    }

    public function test_can_create_work_session_without_route_id(): void
    {
        $session = WorkSession::create([
            'user_id' => $this->rep->id,
            'started_at' => now(),
            'start_latitude' => 0,
            'start_longitude' => 0,
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($session->id);
        $this->assertNull($session->route_id);
        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $this->rep->id,
            'route_id' => null,
        ]);
    }

    public function test_start_work_succeeds_without_gps(): void
    {
        Livewire::actingAs($this->rep)
            ->test(Home::class)
            ->call('startWork')
            ->assertRedirect('/app');

        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $this->rep->id,
            'route_id' => null,
        ]);
    }

    public function test_start_work_succeeds_with_gps(): void
    {
        Livewire::actingAs($this->rep)
            ->test(Home::class)
            ->set('startLat', 24.7136)
            ->set('startLng', 46.6753)
            ->call('startWork')
            ->assertRedirect('/app');

        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $this->rep->id,
            'route_id' => null,
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ]);
    }

    public function test_start_work_does_not_create_duplicate_session(): void
    {
        $payload = ['startLat' => null, 'startLng' => null];

        Livewire::actingAs($this->rep)
            ->test(Home::class, $payload)
            ->call('startWork');

        Livewire::actingAs($this->rep)
            ->test(Home::class, $payload)
            ->call('startWork');

        $this->assertDatabaseCount('work_sessions', 1);
    }
}
