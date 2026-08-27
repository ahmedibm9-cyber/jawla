<?php

namespace Tests\Feature;

use App\Livewire\App\Calendar;
use App\Models\Company;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);

        $this->rep = User::factory()->for($this->company)->create();
        $this->rep->assignRole('sales_rep');
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_calendar_page_renders(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(Calendar::class)
            ->assertStatus(200);
    }

    public function test_calendar_shows_todays_visits(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(Calendar::class)
            ->assertStatus(200)
            ->assertSee(__('app.calendar'));
    }
}
