<?php

namespace Tests\Unit\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\User;
use App\Services\RefundService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundServiceTest extends TestCase
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

    public function test_request_creates_pending_refund(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-001',
            'amount' => 500,
            'remaining_amount' => 500,
            'status' => 'available',
            'reason' => 'Overpayment from invoice',
        ]);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 1000]);

        $refund = app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '100.00',
            method: 'cash',
            reason: 'Customer overpaid',
            intentId: 'intent-001',
        );

        $this->assertSame('pending_approval', $refund->status);
        $this->assertSame('100.00', (string) $refund->amount);
        $this->assertSame('cash', $refund->method);
    }

    public function test_request_rejects_exceeding_credit(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-002',
            'amount' => 50,
            'remaining_amount' => 50,
            'status' => 'available',
            'reason' => 'Overpayment',
        ]);

        $this->expectException(DomainException::class);
        app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '100.00',
            method: 'cash',
            reason: 'Trying to exceed',
            intentId: 'intent-002',
        );
    }

    public function test_request_rejects_invalid_method(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-003',
            'amount' => 500,
            'remaining_amount' => 500,
            'status' => 'available',
            'reason' => 'Overpayment',
        ]);

        $this->expectException(DomainException::class);
        app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '50.00',
            method: 'bitcoin',
            reason: 'Test',
            intentId: 'intent-003',
        );
    }

    public function test_approve_cash_refund_completes_immediately(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $mgr = $this->manager($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-004',
            'amount' => 500,
            'remaining_amount' => 500,
            'status' => 'available',
            'reason' => 'Overpayment',
        ]);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 1000]);

        $refund = app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '200.00',
            method: 'cash',
            reason: 'Refund for return',
            intentId: 'intent-004',
        );

        $result = app(RefundService::class)->approve($refund, $mgr->id);

        $this->assertSame('completed', $result->status);
        $this->assertNotNull($result->completed_at);
        $this->assertSame(300.0, (float) $credit->fresh()->remaining_amount);
        $this->assertSame('available', $credit->fresh()->status);
    }

    public function test_approve_bank_refund_goes_to_pending_external(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $mgr = $this->manager($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-005',
            'amount' => 500,
            'remaining_amount' => 500,
            'status' => 'available',
            'reason' => 'Overpayment',
        ]);

        $refund = app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '150.00',
            method: 'bank',
            reason: 'Bank transfer refund',
            intentId: 'intent-005',
        );

        $result = app(RefundService::class)->approve($refund, $mgr->id);

        $this->assertSame('pending_external', $result->status);
        $this->assertNull($result->completed_at);
    }

    public function test_confirm_external_completes_bank_refund(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $mgr = $this->manager($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-006',
            'amount' => 500,
            'remaining_amount' => 500,
            'status' => 'available',
            'reason' => 'Overpayment',
        ]);

        $refund = app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '150.00',
            method: 'bank',
            reason: 'Bank transfer refund',
            intentId: 'intent-006',
        );

        app(RefundService::class)->approve($refund, $mgr->id);

        $result = app(RefundService::class)->confirmExternal($refund, $mgr->id, 'BANK-REF-123');

        $this->assertSame('completed', $result->status);
        $this->assertSame('BANK-REF-123', $result->external_reference);
        $this->assertNotNull($result->completed_at);
    }

    public function test_request_rejects_empty_reason(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $credit = CustomerCredit::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $rep->id,
            'credit_number' => 'CR-007',
            'amount' => 500,
            'remaining_amount' => 500,
            'status' => 'available',
            'reason' => 'Overpayment',
        ]);

        $this->expectException(DomainException::class);
        app(RefundService::class)->request(
            companyId: $company->id,
            requestedBy: $rep->id,
            customerCreditId: $credit->id,
            amount: '50.00',
            method: 'cash',
            reason: '',
            intentId: 'intent-007',
        );
    }
}
