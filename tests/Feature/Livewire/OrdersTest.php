<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\Orders;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\PurchaseRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_renders_default_invoice_list(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
        ]);

        Livewire::test(Orders::class)
            ->assertSet('type', 'invoices')
            ->assertOk();
    }

    public function test_set_type_switches_tab(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(Orders::class)
            ->call('setType', 'proformas')
            ->assertSet('type', 'proformas');

        Livewire::test(Orders::class)
            ->call('setType', 'offers')
            ->assertSet('type', 'offers');
    }

    public function test_set_type_rejects_invalid(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(Orders::class)
            ->call('setType', 'invalid')
            ->assertSet('type', 'invoices');
    }

    public function test_invoices_tab_shows_user_invoices(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $this->actingAs($rep);

        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
        ]);

        $otherRep = User::factory()->create(['company_id' => $company->id]);
        Invoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $otherRep->id,
            'customer_id' => $customer->id,
        ]);

        Livewire::test(Orders::class)
            ->assertOk();
    }

    public function test_proformas_tab_shows_user_proformas(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        ProformaInvoice::factory()->create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
        ]);

        Livewire::test(Orders::class)
            ->call('setType', 'proformas')
            ->assertOk();
    }

    public function test_offers_tab_shows_user_purchase_requests(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        PurchaseRequest::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'product_id' => Product::factory()->create(['company_id' => $company->id])->id,
            'quantity' => 10,
            'offered_price' => 100,
            'currency' => 'EGP',
            'status' => 'pending',
        ]);

        Livewire::test(Orders::class)
            ->call('setType', 'offers')
            ->assertOk();
    }
}
