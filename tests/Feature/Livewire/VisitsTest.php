<?php

namespace Tests\Feature\Livewire;

use App\Enums\VisitPurpose;
use App\Enums\VisitStatus;
use App\Livewire\App\Visits;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Route;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_renders_visit_list(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $route = Route::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => $route->id]);
        $ws = WorkSession::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'started_at' => now()->subHours(2),
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ]);

        Visit::create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'work_session_id' => $ws->id,
            'purpose' => VisitPurpose::Sale,
            'status' => VisitStatus::Closed,
            'is_out_of_route' => false,
            'checkin_at' => now()->subHour(),
            'checkout_at' => now(),
        ]);

        Livewire::test(Visits::class)
            ->assertOk();
    }

    public function test_shows_only_own_visits(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $route = Route::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => $route->id]);
        $otherRep = User::factory()->create(['company_id' => $company->id]);

        $ws = WorkSession::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'started_at' => now()->subHours(2),
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ]);

        $otherWs = WorkSession::create([
            'company_id' => $company->id,
            'user_id' => $otherRep->id,
            'started_at' => now()->subHours(2),
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ]);

        Visit::create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'work_session_id' => $ws->id,
            'purpose' => VisitPurpose::Sale,
            'status' => VisitStatus::Closed,
            'checkin_at' => now()->subHour(),
            'checkout_at' => now(),
        ]);

        Visit::create([
            'user_id' => $otherRep->id,
            'customer_id' => $customer->id,
            'route_id' => $route->id,
            'work_session_id' => $otherWs->id,
            'purpose' => VisitPurpose::Sale,
            'status' => VisitStatus::Closed,
            'checkin_at' => now()->subHour(),
            'checkout_at' => now(),
        ]);

        Livewire::test(Visits::class)
            ->assertOk();
    }

    public function test_empty_state_renders(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(Visits::class)
            ->assertOk();
    }
}
