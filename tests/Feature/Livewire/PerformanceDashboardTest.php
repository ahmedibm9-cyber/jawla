<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\PerformanceDashboard;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function rep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    public function test_dashboard_renders(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(PerformanceDashboard::class)
            ->assertStatus(200);
    }

    public function test_period_filter(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(PerformanceDashboard::class)
            ->assertSet('period', 'today')
            ->set('period', 'week')
            ->assertSet('period', 'week')
            ->set('period', 'month')
            ->assertSet('period', 'month');
    }

    public function test_metrics_computed(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(PerformanceDashboard::class)
            ->assertSet('metrics', fn ($metrics) => is_array($metrics) && array_key_exists('totalVisits', $metrics));
    }
}
