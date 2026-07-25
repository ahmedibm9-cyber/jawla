<?php

namespace Tests\Unit\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\User;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_expense_creates_expense_and_decrements_cashbox(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 500]);

        $expense = app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'fuel',
            amount: 75.50,
            note: 'Gas for van',
        );

        $this->assertSame(75.50, (float) $expense->amount);
        $this->assertSame('fuel', $expense->category);
        $this->assertSame(500.0 - 75.50, (float) CashBox::where('user_id', $rep->id)->first()->balance);
    }

    public function test_log_expense_auto_creates_cashbox_if_missing(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        // Create cashbox with sufficient balance
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 100]);

        app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'food',
            amount: 30.0,
        );

        $cashBox = CashBox::where('user_id', $rep->id)->first();
        $this->assertNotNull($cashBox);
        $this->assertSame(70.0, (float) $cashBox->balance);
    }

    public function test_log_expense_rejects_when_balance_insufficient(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 10]);

        $this->expectException(DomainException::class);

        app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'fuel',
            amount: 50.0,
        );
    }

    public function test_cancel_expense_restores_cashbox(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 200]);

        $expense = app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'maintenance',
            amount: 50.0,
        );

        $this->assertSame(150.0, (float) CashBox::where('user_id', $rep->id)->first()->balance);

        app(ExpenseService::class)->cancel($expense, $rep->id);

        $fresh = $expense->fresh();
        $this->assertSame(200.0, (float) CashBox::where('user_id', $rep->id)->first()->balance);
        $this->assertNotNull($fresh);
        $this->assertNotNull($fresh->cancelled_at);
        $this->assertSame($rep->id, $fresh->cancelled_by);
    }

    public function test_cancel_expense_works_without_cashbox(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        // Create cashbox with sufficient balance
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 100]);

        $expense = app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'food',
            amount: 20.0,
        );

        app(ExpenseService::class)->cancel($expense, $rep->id);

        $fresh = $expense->fresh();
        $this->assertNotNull($fresh->cancelled_at);
        $this->assertSame($rep->id, $fresh->cancelled_by);
    }

    public function test_cancelling_an_expense_twice_does_not_credit_cash_twice(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 200]);
        $expense = app(ExpenseService::class)->log($company->id, $rep->id, 'fuel', 50);

        app(ExpenseService::class)->cancel($expense, $rep->id);
        app(ExpenseService::class)->cancel($expense, $rep->id);

        $this->assertSame(200.0, (float) CashBox::where('user_id', $rep->id)->firstOrFail()->balance);
    }

    public function test_log_expense_rejects_zero_amount(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 100]);

        $this->expectException(DomainException::class);

        app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'fuel',
            amount: 0,
        );
    }

    public function test_log_expense_rejects_negative_amount(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        CashBox::create(['user_id' => $rep->id, 'company_id' => $company->id, 'balance' => 100]);

        $this->expectException(DomainException::class);

        app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'fuel',
            amount: -50,
        );
    }
}
