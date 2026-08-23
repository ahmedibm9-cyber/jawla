<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyVisitAssignment;
use App\Models\User;
use App\Services\DailyVisitAssignmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyVisitAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeRep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    // ── submit ────────────────────────────────────────────────────────

    public function test_submit_moves_draft_to_pending_approval(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $result = app(DailyVisitAssignmentService::class)->submit($assignment, $admin);

        $this->assertSame('pending_approval', $result->status);
        $this->assertNotNull($result->submitted_at);
        $this->assertNotNull($result->latestApproval);
    }

    public function test_submit_rejects_unauthorized_user(): void
    {
        $company = Company::factory()->create();
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $rep->id,
        ]);

        $this->expectException(AuthorizationException::class);
        app(DailyVisitAssignmentService::class)->submit($assignment, $rep);
    }

    public function test_submit_rejects_non_draft_assignment(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'approved',
            'assigned_by' => $admin->id,
        ]);

        $this->expectException(\DomainException::class);
        app(DailyVisitAssignmentService::class)->submit($assignment, $admin);
    }

    // ── approve ───────────────────────────────────────────────────────

    public function test_approve_sets_status_to_approved(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $submitted = app(DailyVisitAssignmentService::class)->submit($assignment, $admin);
        $approved = app(DailyVisitAssignmentService::class)->approve($submitted->latestApproval, $admin);

        $this->assertSame('approved', $approved->status);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_approve_rejects_unauthorized_user(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $submitted = app(DailyVisitAssignmentService::class)->submit($assignment, $admin);

        $this->expectException(AuthorizationException::class);
        app(DailyVisitAssignmentService::class)->approve($submitted->latestApproval, $rep);
    }

    // ── reject ────────────────────────────────────────────────────────

    public function test_reject_sets_status_to_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $submitted = app(DailyVisitAssignmentService::class)->submit($assignment, $admin);
        $rejected = app(DailyVisitAssignmentService::class)->reject($submitted->latestApproval, $admin, 'Incomplete plan');

        $this->assertSame('rejected', $rejected->status);
    }

    public function test_reject_rejects_empty_reason(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $submitted = app(DailyVisitAssignmentService::class)->submit($assignment, $admin);

        $this->expectException(\DomainException::class);
        app(DailyVisitAssignmentService::class)->reject($submitted->latestApproval, $admin, '');
    }

    public function test_reject_rejects_unauthorized_user(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $assignment = DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $submitted = app(DailyVisitAssignmentService::class)->submit($assignment, $admin);

        $this->expectException(AuthorizationException::class);
        app(DailyVisitAssignmentService::class)->reject($submitted->latestApproval, $rep, 'Nope');
    }

    // ── bulkAssign ────────────────────────────────────────────────────

    public function test_bulk_assign_creates_assignments(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customers = Customer::factory()->count(3)->create(['company_id' => $company->id]);

        $result = app(DailyVisitAssignmentService::class)->bulkAssign(
            $admin,
            $rep->id,
            today()->toDateString(),
            $customers->pluck('id')->toArray(),
        );

        $this->assertSame(3, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertDatabaseHas('daily_visit_assignments', [
            'user_id' => $rep->id,
            'customer_id' => $customers[0]->id,
            'status' => 'draft',
        ]);
    }

    public function test_bulk_assign_skips_existing(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeAdmin($company);
        $rep = $this->makeRep($company);
        $customers = Customer::factory()->count(2)->create(['company_id' => $company->id]);

        DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customers[0]->id,
            'visit_date' => today(),
            'status' => 'draft',
            'assigned_by' => $admin->id,
        ]);

        $result = app(DailyVisitAssignmentService::class)->bulkAssign(
            $admin,
            $rep->id,
            today()->toDateString(),
            $customers->pluck('id')->toArray(),
        );

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_bulk_assign_rejects_unauthorized_user(): void
    {
        $company = Company::factory()->create();
        $rep = $this->makeRep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);
        app(DailyVisitAssignmentService::class)->bulkAssign(
            $rep,
            $rep->id,
            today()->toDateString(),
            [$customer->id],
        );
    }
}
