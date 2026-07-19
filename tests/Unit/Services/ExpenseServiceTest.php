<?php

namespace Tests\Unit\Services;

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

        app(ExpenseService::class)->log(
            companyId: $company->id,
            userId: $rep->id,
            category: 'food',
            amount: 30.0,
        );

        $cashBox = CashBox::where('user_id', $rep->id)->first();
        $this->assertNotNull($cashBox);
        $this->assertSame(-30.0, (float) $cashBox->balance);
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
}
