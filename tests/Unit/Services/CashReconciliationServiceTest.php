<?php

namespace Tests\Unit\Services;

use App\Models\CashBox;
use App\Models\Company;
use App\Models\User;
use App\Services\CashReconciliationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashReconciliationServiceTest extends TestCase
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

    private function manager(Company $company): User
    {
        $mgr = User::factory()->create(['company_id' => $company->id]);
        $mgr->assignRole('sales_manager');

        return $mgr;
    }

    public function test_submit_creates_reconciliation_with_variance(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 500]);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 520.0,
            notes: 'Over by 20',
        );

        $this->assertSame('pending', $rec->status);
        $this->assertSame(500.0, (float) $rec->expected_amount);
        $this->assertSame(520.0, (float) $rec->counted_amount);
        $this->assertSame(20.0, (float) $rec->variance);
    }

    public function test_submit_zero_variance_when_counts_match(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 300]);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 300.0,
        );

        $this->assertSame(0.0, (float) $rec->variance);
    }

    public function test_submit_negative_variance_when_short(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 500]);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 450.0,
        );

        $this->assertSame(-50.0, (float) $rec->variance);
    }

    public function test_submit_without_cashbox_uses_zero_expected(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 100.0,
        );

        $this->assertSame(0.0, (float) $rec->expected_amount);
        $this->assertSame(100.0, (float) $rec->variance);
    }

    public function test_approve_sets_status_and_reviewer(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $mgr = $this->manager($company);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 500]);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 500.0,
        );

        $approved = app(CashReconciliationService::class)->approve($rec, $mgr->id, 'Looks good');

        $this->assertSame('approved', $approved->status);
        $this->assertSame($mgr->id, $approved->reviewed_by);
        $this->assertNotNull($approved->reviewed_at);
        $this->assertSame('Looks good', $approved->review_notes);
    }

    public function test_flag_sets_flagged_status(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $mgr = $this->manager($company);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 500]);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 400.0,
        );

        $flagged = app(CashReconciliationService::class)->flag($rec, $mgr->id, 'Short by 100');

        $this->assertSame('flagged', $flagged->status);
        $this->assertSame($mgr->id, $flagged->reviewed_by);
    }

    public function test_review_rejects_already_reviewed(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $mgr = $this->manager($company);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 500]);

        $rec = app(CashReconciliationService::class)->submit(
            companyId: $company->id,
            userId: $rep->id,
            countedAmount: 500.0,
        );

        app(CashReconciliationService::class)->approve($rec, $mgr->id);

        $this->expectException(\RuntimeException::class);
        app(CashReconciliationService::class)->flag($rec, $mgr->id, 'Double review');
    }
}
