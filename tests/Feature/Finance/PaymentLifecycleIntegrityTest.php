<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLifecycleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_overpayment_is_allocated_and_preserved_as_customer_credit(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 100]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'status' => InvoiceStatus::Issued,
            'total' => 100,
            'paid_amount' => 0,
            'remaining_amount' => 100,
        ]);

        $payment = app(PaymentService::class)->collect(
            $company->id,
            $rep->id,
            $customer->id,
            125,
            'cash',
            $invoice->id,
            notes: 'Customer tendered extra cash',
            intentId: 'payment-intent-1',
        );

        $this->assertSame('100.00', $payment->allocated_amount);
        $this->assertSame('25.00', $payment->unallocated_amount);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->remaining_amount);
        $this->assertSame('0.00', $customer->fresh()->balance);
        $this->assertDatabaseHas('customer_credits', [
            'payment_id' => $payment->id,
            'amount' => '25.00',
            'remaining_amount' => '25.00',
            'status' => 'available',
        ]);

        $duplicate = app(PaymentService::class)->collect(
            $company->id,
            $rep->id,
            $customer->id,
            125,
            'cash',
            $invoice->id,
            notes: 'retry',
            intentId: 'payment-intent-1',
        );
        $this->assertSame($payment->id, $duplicate->id);
        $this->assertDatabaseCount('payments', 1);

        try {
            app(PaymentService::class)->collect(
                $company->id,
                $rep->id,
                $customer->id,
                124,
                'cash',
                $invoice->id,
                intentId: 'payment-intent-1',
            );
            $this->fail('A reused payment intent accepted a different payload.');
        } catch (DomainException) {
            $this->assertDatabaseCount('payments', 1);
        }
    }

    public function test_payment_to_terminal_invoice_is_rejected_before_any_write(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'status' => InvoiceStatus::Credited,
            'remaining_amount' => 0,
        ]);

        $this->expectException(DomainException::class);
        try {
            app(PaymentService::class)->collect(
                $company->id,
                $rep->id,
                $customer->id,
                10,
                'cash',
                $invoice->id,
            );
        } finally {
            $this->assertDatabaseCount('payments', 0);
            $this->assertDatabaseCount('customer_credits', 0);
        }
    }
}
