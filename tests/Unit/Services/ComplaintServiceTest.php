<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkSession;
use App\Services\ComplaintService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintServiceTest extends TestCase
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

    public function test_log_creates_complaint(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $complaint = app(ComplaintService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            type: 'quality_issue',
            description: 'Product was damaged',
        );

        $this->assertSame('open', $complaint->status);
        $this->assertSame('quality_issue', $complaint->complaint_type);
        $this->assertSame('Product was damaged', $complaint->description);
        $this->assertSame($customer->id, $complaint->customer_id);
    }

    public function test_resolve_sets_status_and_resolution(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('sales_manager');
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $complaint = app(ComplaintService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            type: 'quality_issue',
            description: 'Product was damaged',
        );

        $resolved = app(ComplaintService::class)->resolve($complaint, $manager->id, 'Replacement shipped');

        $this->assertSame('resolved', $resolved->status);
        $this->assertSame('Replacement shipped', $resolved->resolution);
        $this->assertSame($manager->id, $resolved->assigned_to);
        $this->assertNotNull($resolved->resolved_at);
    }

    public function test_log_attaches_to_visit_when_provided(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $workSession = WorkSession::factory()->create([
            'user_id' => $rep->id,
            'company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
        ]);

        $complaint = app(ComplaintService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            customerId: $customer->id,
            type: 'delivery_issue',
            description: 'Order late',
            visitId: $visit->id,
        );

        $this->assertSame($visit->id, $complaint->visit_id);
    }
}
