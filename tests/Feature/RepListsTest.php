<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Livewire\App\Orders;
use App\Livewire\App\Visits;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RepListsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $otherRep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->rep = $this->makeRep($this->company);
        $this->otherRep = $this->makeRep($this->company);
    }

    private function makeRep(Company $company): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('rep');

        return $user;
    }

    private function makeVisit(User $rep, string $customerName): Visit
    {
        return Visit::factory()->create([
            'user_id' => $rep->id,
            'work_session_id' => WorkSession::factory()->create(['user_id' => $rep->id])->id,
            'customer_id' => Customer::factory()->create([
                'company_id' => $rep->company_id,
                'name_ar' => $customerName,
            ])->id,
            'status' => VisitStatus::Closed,
        ]);
    }

    public function test_visits_page_lists_only_own_visits(): void
    {
        $this->makeVisit($this->rep, 'عميل المندوب الأول');
        $this->makeVisit($this->otherRep, 'عميل المندوب الآخر');

        Livewire::actingAs($this->rep)
            ->test(Visits::class)
            ->assertSee('عميل المندوب الأول')
            ->assertDontSee('عميل المندوب الآخر');
    }

    public function test_visits_page_shows_empty_state(): void
    {
        Livewire::actingAs($this->rep)
            ->test(Visits::class)
            ->assertSee(__('app.no_visits_yet'));
    }

    public function test_orders_page_lists_only_own_invoices(): void
    {
        $mine = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
            'invoice_number' => 'INV-MINE-1',
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->otherRep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
            'invoice_number' => 'INV-THEIRS-1',
        ]);

        Livewire::actingAs($this->rep)
            ->test(Orders::class)
            ->assertSee('INV-MINE-1')
            ->assertDontSee('INV-THEIRS-1')
            ->assertSee(route('app.pdf.invoice', $mine), false);
    }

    public function test_orders_page_toggles_to_proformas(): void
    {
        ProformaInvoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
            'proforma_number' => 'PF-MINE-1',
        ]);

        Livewire::actingAs($this->rep)
            ->test(Orders::class)
            ->assertDontSee('PF-MINE-1')
            ->call('setType', 'proformas')
            ->assertSee('PF-MINE-1');
    }

    public function test_orders_page_rejects_unknown_type(): void
    {
        Livewire::actingAs($this->rep)
            ->test(Orders::class)
            ->call('setType', 'evil')
            ->assertSet('type', 'invoices');
    }

    public function test_orders_page_shows_empty_state_with_action(): void
    {
        Livewire::actingAs($this->rep)
            ->test(Orders::class)
            ->assertSee(__('app.no_orders_yet'))
            ->assertSee('/app/sell', false);
    }

    public function test_visits_page_is_paginated(): void
    {
        for ($i = 0; $i < 26; $i++) {
            $this->makeVisit($this->rep, "عميل رقم {$i}");
        }

        $component = Livewire::actingAs($this->rep)->test(Visits::class);

        $this->assertCount(25, $component->viewData('visits')->items());
    }

    public function test_routes_require_rep_auth(): void
    {
        $this->get('/app/visits')->assertRedirect();
        $this->get('/app/orders')->assertRedirect();

        $this->actingAs($this->rep)->get('/app/visits')->assertOk();
        $this->actingAs($this->rep)->get('/app/orders')->assertOk();
    }
}
