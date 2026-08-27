<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\Tickets;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function rep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    public function test_create_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(Tickets::class)
            ->set('newTitle', '')
            ->set('newDescription', '')
            ->call('createTicket')
            ->assertHasErrors(['newTitle', 'newDescription']);
    }

    public function test_create_ticket(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'route_id' => null]);
        $this->actingAs($rep);

        Livewire::test(Tickets::class)
            ->set('newTitle', 'Delivery issue')
            ->set('newDescription', 'Customer reported late delivery')
            ->set('newCustomerId', $customer->id)
            ->set('newPriority', 'high')
            ->call('createTicket')
            ->assertSet('successMessage', fn ($msg) => $msg !== null)
            ->assertSet('showCreateForm', false);

        $this->assertDatabaseHas('tickets', [
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'title' => 'Delivery issue',
            'priority' => 'high',
            'status' => 'new',
        ]);
    }

    public function test_transition_status(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);
        app(ActiveCompanyContext::class)->setCompanyId($company->id);

        $ticket = Ticket::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'title' => 'Test ticket',
            'description' => 'Test description',
            'priority' => 'medium',
            'status' => 'new',
        ]);

        Livewire::test(Tickets::class)
            ->call('transitionStatus', $ticket->id, 'in_progress')
            ->assertSet('successMessage', fn ($msg) => $msg !== null);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id' => $ticket->id,
            'old_status' => 'new',
            'new_status' => 'in_progress',
        ]);
    }

    public function test_filter_by_status(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(Tickets::class)
            ->set('statusFilter', 'in_progress')
            ->assertSet('statusFilter', 'in_progress');
    }

    public function test_toggle_view_mode(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(Tickets::class)
            ->assertSet('viewMode', 'table')
            ->call('toggleViewMode')
            ->assertSet('viewMode', 'kanban')
            ->call('toggleViewMode')
            ->assertSet('viewMode', 'table');
    }
}
