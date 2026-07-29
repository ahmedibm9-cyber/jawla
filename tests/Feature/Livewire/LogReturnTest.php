<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\LogReturn;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_mount_adds_one_empty_item(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogReturn::class)
            ->assertSet('items', [['invoice_item_id' => '', 'quantity' => 1, 'condition' => 'sellable']]);
    }

    public function test_add_item_adds_row(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogReturn::class)
            ->call('addItem')
            ->assertSet('items', [
                ['invoice_item_id' => '', 'quantity' => 1, 'condition' => 'sellable'],
                ['invoice_item_id' => '', 'quantity' => 1, 'condition' => 'sellable'],
            ]);
    }

    public function test_remove_item_removes_row(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogReturn::class)
            ->call('addItem')
            ->call('removeItem', 0)
            ->assertSet('items', [['invoice_item_id' => '', 'quantity' => 1, 'condition' => 'sellable']]);
    }

    public function test_submit_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogReturn::class)
            ->call('submit')
            ->assertHasErrors(['customer_id', 'against_invoice_id', 'reason']);
    }

    public function test_queue_offline_sets_success(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(LogReturn::class)
            ->call('queueOffline')
            ->assertSet('success', true)
            ->assertSet('successMessage', fn ($msg) => str_contains($msg, 'offline') || str_contains($msg, 'دون اتصال'));
    }
}
