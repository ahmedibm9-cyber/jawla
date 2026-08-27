<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketServiceTest extends TestCase
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

    public function test_create_ticket(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'route_id' => null]);
        $service = app(TicketService::class);

        $ticket = $service->create($this->company->id, $this->rep->id, [
            'title' => 'Delivery issue',
            'description' => 'Customer reported late delivery',
            'customer_id' => $customer->id,
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Delivery issue',
            'priority' => 'high',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id' => $ticket->id,
            'old_status' => null,
            'new_status' => 'new',
        ]);
    }

    public function test_transition_to(): void
    {
        $ticket = Ticket::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test ticket',
            'description' => 'Test description',
            'priority' => 'medium',
            'status' => 'new',
        ]);

        $service = app(TicketService::class);
        $transitioned = $service->transitionTo($ticket, 'in_progress', $this->rep->id, 'Starting work');

        $this->assertEquals('in_progress', $transitioned->status);
        $this->assertDatabaseHas('ticket_status_history', [
            'ticket_id' => $ticket->id,
            'old_status' => 'new',
            'new_status' => 'in_progress',
            'notes' => 'Starting work',
        ]);
    }

    public function test_assign(): void
    {
        $ticket = Ticket::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test ticket',
            'description' => 'Test description',
            'priority' => 'medium',
            'status' => 'new',
        ]);

        $assignee = User::factory()->for($this->company)->create();
        $service = app(TicketService::class);
        $assigned = $service->assign($ticket, $assignee->id);

        $this->assertEquals($assignee->id, $assigned->assigned_to);
    }

    public function test_get_for_company(): void
    {
        Ticket::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test ticket',
            'description' => 'Test description',
            'priority' => 'medium',
            'status' => 'new',
        ]);

        $service = app(TicketService::class);
        $tickets = $service->getForCompany($this->company->id);

        $this->assertCount(1, $tickets);
    }

    public function test_get_for_user(): void
    {
        Ticket::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test ticket',
            'description' => 'Test description',
            'priority' => 'medium',
            'status' => 'new',
        ]);

        $service = app(TicketService::class);
        $tickets = $service->getForUser($this->rep->id);

        $this->assertCount(1, $tickets);
    }
}
