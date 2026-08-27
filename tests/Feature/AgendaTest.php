<?php

namespace Tests\Feature;

use App\Livewire\App\Agenda;
use App\Models\Company;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaTest extends TestCase
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

    public function test_agenda_page_renders(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(Agenda::class)
            ->assertStatus(200);
    }

    public function test_non_planned_visit_form_toggle(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(Agenda::class)
            ->set('showNonPlannedForm', true)
            ->assertSet('showNonPlannedForm', true);
    }

    public function test_toggle_non_planned_form(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(Agenda::class)
            ->assertSet('showNonPlannedForm', false)
            ->set('showNonPlannedForm', true)
            ->assertSet('showNonPlannedForm', true);
    }
}
