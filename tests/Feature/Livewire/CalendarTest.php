<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\Calendar;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
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

    public function test_calendar_renders(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(Calendar::class)
            ->assertStatus(200);
    }

    public function test_navigation(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        $initialMonth = now()->format('Y-m');

        Livewire::test(Calendar::class)
            ->assertSet('currentMonth', $initialMonth)
            ->call('nextMonth')
            ->assertSet('currentMonth', now()->addMonth()->format('Y-m'))
            ->call('previousMonth')
            ->assertSet('currentMonth', $initialMonth);
    }

    public function test_select_date(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        $date = now()->addDay()->format('Y-m-d');

        Livewire::test(Calendar::class)
            ->call('selectDate', $date)
            ->assertSet('selectedDate', $date);
    }
}
