<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Services\CallService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallServiceTest extends TestCase
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

    public function test_create_call(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'route_id' => null]);
        $service = app(CallService::class);

        $call = $service->create($this->company->id, $this->rep->id, [
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'duration_seconds' => 120,
            'outcome' => 'reached',
            'notes' => 'Test call',
        ]);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'duration_seconds' => 120,
            'outcome' => 'reached',
        ]);
    }

    public function test_get_for_customer(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'route_id' => null]);
        $service = app(CallService::class);

        $service->create($this->company->id, $this->rep->id, [
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'duration_seconds' => 60,
            'outcome' => 'reached',
        ]);

        $calls = $service->getForCustomer($customer->id);

        $this->assertCount(1, $calls);
        $this->assertEquals($customer->id, $calls->first()->customer_id);
    }

    public function test_get_for_company(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'route_id' => null]);
        $service = app(CallService::class);

        $service->create($this->company->id, $this->rep->id, [
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'duration_seconds' => 60,
            'outcome' => 'reached',
        ]);

        $calls = $service->getForCompany($this->company->id);

        $this->assertCount(1, $calls);
    }

    public function test_get_for_company_with_filters(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id, 'route_id' => null]);
        $service = app(CallService::class);

        $service->create($this->company->id, $this->rep->id, [
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'duration_seconds' => 60,
            'outcome' => 'reached',
            'called_at' => now()->subDays(5),
        ]);

        $service->create($this->company->id, $this->rep->id, [
            'customer_id' => $customer->id,
            'direction' => 'inbound',
            'duration_seconds' => 30,
            'outcome' => 'no_answer',
            'called_at' => now(),
        ]);

        $calls = $service->getForCompany($this->company->id, ['date_from' => now()->subDay()]);

        $this->assertCount(1, $calls);
        $this->assertEquals('inbound', $calls->first()->direction);
    }
}
