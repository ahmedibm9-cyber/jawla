<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\LogExpense;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_submit_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogExpense::class)
            ->set('amount', null)
            ->call('submit')
            ->assertHasErrors(['amount']);
    }

    public function test_submit_rejects_when_amount_exceeds_balance(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        CashBox::create(['company_id' => $company->id, 'user_id' => $rep->id, 'balance' => 50.0]);
        $this->actingAs($rep);

        Livewire::test(LogExpense::class)
            ->set('amount', 100.0)
            ->call('submit')
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg, 'exceeds') || str_contains($msg, 'يتجاوز'));
    }

    public function test_submit_creates_expense(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        CashBox::create(['company_id' => $company->id, 'user_id' => $rep->id, 'balance' => 500.0]);
        $this->actingAs($rep);

        Livewire::test(LogExpense::class)
            ->set('category', 'fuel')
            ->set('amount', 75.5)
            ->set('note', 'Gas station')
            ->call('submit')
            ->assertSet('success', true)
            ->assertSet('errorMessage', '');
    }

    public function test_queue_offline_sets_success(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogExpense::class)
            ->call('queueOffline')
            ->assertSet('success', true)
            ->assertSet('successMessage', fn ($msg) => str_contains($msg, 'offline') || str_contains($msg, 'دون اتصال'));
    }
}
