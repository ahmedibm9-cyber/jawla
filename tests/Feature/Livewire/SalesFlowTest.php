<?php

namespace Tests\Feature\Livewire;

use App\Enums\StockReason;
use App\Livewire\App\SalesFlow;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function repWithVan(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'van', 'user_id' => $rep->id, 'is_active' => true]);

        return $rep;
    }

    public function test_renders_cart_step_by_default(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->repWithVan($company);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->assertSet('step', 'cart');
    }

    public function test_add_to_cart_increments_quantity(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->repWithVan($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 50]);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->assertSet('cart.0.quantity', 2)
            ->assertSet('cartTotal', 100.0);
    }

    public function test_remove_item_clears_cart(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->repWithVan($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 50]);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->call('addToCart', $product->id)
            ->assertSet('cart', [0 => [
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 50.0,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'vat_applicable' => $product->vat_applicable,
                'line_total' => '50.00',
                'vat_amount' => '0.00',
            ]])
            ->call('removeItem', 0)
            ->assertSet('cart', []);
    }

    public function test_submit_creates_invoice_for_authorized_rep(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->repWithVan($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $van = Warehouse::where('user_id', $rep->id)->where('company_id', $company->id)->first();
        $this->actingAs($rep);

        app(StockService::class)->increment($van->id, $product->id, null, 10, StockReason::Initial, $product);

        Livewire::test(SalesFlow::class)
            ->call('selectCustomer', $customer->id)
            ->call('addToCart', $product->id)
            ->call('submit')
            ->assertSet('step', 'done')
            ->assertSet('createdInvoiceId', fn ($id) => $id > 0);
    }

    public function test_submit_rejects_empty_cart(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithVan($company);
        $customer = Customer::factory()->create(['company_id' => $company->id, 'status' => 'approved']);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->call('selectCustomer', $customer->id)
            ->call('submit')
            ->assertSet('errorMessage', fn ($msg) => str_contains($msg, 'product') || str_contains($msg, 'منتجات'));
    }

    public function test_submit_rejects_no_customer(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithVan($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->call('addToCart', $product->id)
            ->call('submit')
            ->assertHasErrors(['customerId']);
    }

    public function test_update_qty_removes_item_when_zero(): void
    {
        $company = Company::factory()->create(['vat_percent' => 0]);
        $rep = $this->repWithVan($company);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 50]);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->call('addToCart', $product->id)
            ->call('updateQty', 0, 0)
            ->assertSet('cart', []);
    }

    public function test_queue_offline_shows_queued_message(): void
    {
        $company = Company::factory()->create();
        $rep = $this->repWithVan($company);
        $this->actingAs($rep);

        Livewire::test(SalesFlow::class)
            ->call('queueOffline')
            ->assertSet('step', 'queued')
            ->assertSet('successMessage', fn ($msg) => str_contains($msg, 'offline') || str_contains($msg, 'دون اتصال'));
    }
}
