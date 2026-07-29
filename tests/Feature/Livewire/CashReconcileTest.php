<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\CashReconcile;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashReconcileTest extends TestCase
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

        Livewire::test(CashReconcile::class)
            ->call('submit')
            ->assertHasErrors(['counted_amount']);
    }

    public function test_submit_creates_reconciliation(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        CashBox::create(['company_id' => $company->id, 'user_id' => $rep->id, 'balance' => 300.0]);
        $this->actingAs($rep);

        Livewire::test(CashReconcile::class)
            ->set('counted_amount', 300.0)
            ->call('submit')
            ->assertSet('success', true)
            ->assertSet('errorMessage', '');
    }

    public function test_submit_shows_variance_message(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        CashBox::create(['company_id' => $company->id, 'user_id' => $rep->id, 'balance' => 300.0]);
        $this->actingAs($rep);

        Livewire::test(CashReconcile::class)
            ->set('counted_amount', 320.0)
            ->call('submit')
            ->assertSet('success', true)
            ->assertSet('successMessage', fn ($msg) => str_contains($msg, '20.00') || str_contains($msg, 'variance'));
    }
}
