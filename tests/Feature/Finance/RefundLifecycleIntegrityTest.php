<?php

namespace Tests\Feature\Finance;

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

class RefundLifecycleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $manager;

    private CustomerCredit $credit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('sales_rep');
        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        $this->manager->assignRole('sales_manager');
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->credit = CustomerCredit::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'created_by' => $this->rep->id,
            'credit_number' => 'CREDIT-RETURN-001',
            'amount' => '200.00',
            'remaining_amount' => '200.00',
            'status' => 'available',
            'reason' => 'Paid invoice return',
        ]);
        CashBox::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'balance' => '150.00',
        ]);
    }

    public function test_manager_approved_cash_refund_is_a_separate_immutable_cash_and_credit_posting(): void
    {
        $refund = app(RefundService::class)->request(
            $this->company->id,
            $this->rep->id,
            $this->credit->id,
            '100.00',
            'cash',
            'Customer requested cash',
            'refund-intent-1',
        );

        $completed = app(RefundService::class)->approve($refund, $this->manager->id);

        $this->assertSame('completed', $completed->status);
        $this->assertSame('50.00', CashBox::where('user_id', $this->rep->id)->value('balance'));
        $this->assertSame('100.00', $this->credit->fresh()->remaining_amount);
        $this->assertNotNull($completed->approved_at);
        $this->assertNotNull($completed->completed_at);
    }

    public function test_cash_refund_rejects_insufficient_cash_without_consuming_credit(): void
    {
        $refund = app(RefundService::class)->request(
            $this->company->id,
            $this->rep->id,
            $this->credit->id,
            '160.00',
            'cash',
            'Customer requested cash',
            'refund-intent-2',
        );

        $this->expectException(DomainException::class);
        try {
            app(RefundService::class)->approve($refund, $this->manager->id);
        } finally {
            $this->assertSame('pending_approval', $refund->fresh()->status);
            $this->assertSame('150.00', CashBox::where('user_id', $this->rep->id)->value('balance'));
            $this->assertSame('200.00', $this->credit->fresh()->remaining_amount);
        }
    }

    public function test_bank_refund_stays_pending_until_external_confirmation_and_retries_are_idempotent(): void
    {
        $service = app(RefundService::class);
        $refund = $service->request(
            $this->company->id,
            $this->rep->id,
            $this->credit->id,
            '80.00',
            'bank',
            'Return to customer bank',
            'refund-intent-3',
        );

        $pending = $service->approve($refund, $this->manager->id);
        $this->assertSame('pending_external', $pending->status);
        $this->assertNull($pending->completed_at);
        $this->assertSame('120.00', $this->credit->fresh()->remaining_amount);

        $this->assertSame($pending->id, $service->approve($pending, $this->manager->id)->id);
        $completed = $service->confirmExternal($pending, $this->manager->id, 'BANK-REF-123');
        $this->assertSame('completed', $completed->status);
        $this->assertSame('BANK-REF-123', $completed->external_reference);
        $this->assertSame($completed->id, $service->confirmExternal($completed, $this->manager->id, 'BANK-REF-123')->id);
    }

    public function test_sales_rep_cannot_approve_a_refund(): void
    {
        $refund = app(RefundService::class)->request(
            $this->company->id,
            $this->rep->id,
            $this->credit->id,
            '10.00',
            'cash',
            'Customer requested cash',
            'refund-intent-4',
        );

        $this->expectException(DomainException::class);
        app(RefundService::class)->approve($refund, $this->rep->id);
    }
}
