<?php

namespace Tests\Feature;

use App\Livewire\App\SalesFlow;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Wave-1 barcode scan → product lookup in the Sales Flow. scanBarcode resolves a
 * scanned/entered code to a product (barcode first, SKU fallback), company
 * scoped and active-only, and adds it to the cart.
 */
class BarcodeScanTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $this->company->id]);
        $this->rep->assignRole('rep');
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function product(array $attrs): Product
    {
        return Product::factory()->create(array_merge(['company_id' => $this->company->id, 'is_active' => true], $attrs));
    }

    public function test_scan_adds_product_by_barcode(): void
    {
        $p = $this->product(['barcode' => '6291041500213']);

        $component = Livewire::actingAs($this->rep)
            ->test(SalesFlow::class)
            ->call('scanBarcode', '6291041500213');

        $cart = $component->get('cart');
        $this->assertCount(1, $cart);
        $this->assertSame($p->id, $cart[0]['product_id']);
    }

    public function test_scan_falls_back_to_sku(): void
    {
        $p = $this->product(['sku' => 'SKU-XYZ-1', 'barcode' => null]);

        $component = Livewire::actingAs($this->rep)
            ->test(SalesFlow::class)
            ->call('scanBarcode', 'SKU-XYZ-1');

        $this->assertCount(1, $component->get('cart'));
        $this->assertSame($p->id, $component->get('cart')[0]['product_id']);
    }

    public function test_unknown_code_sets_error_and_adds_nothing(): void
    {
        $component = Livewire::actingAs($this->rep)
            ->test(SalesFlow::class)
            ->call('scanBarcode', 'does-not-exist');

        $this->assertCount(0, $component->get('cart'));
        $this->assertStringContainsString('does-not-exist', $component->get('errorMessage'));
    }

    public function test_inactive_product_is_not_matched(): void
    {
        $this->product(['barcode' => 'INACTIVE1', 'is_active' => false]);

        $component = Livewire::actingAs($this->rep)
            ->test(SalesFlow::class)
            ->call('scanBarcode', 'INACTIVE1');

        $this->assertCount(0, $component->get('cart'));
    }

    public function test_barcode_lookup_is_company_scoped(): void
    {
        $other = Company::factory()->create();
        Product::factory()->create(['company_id' => $other->id, 'barcode' => 'SHARED9', 'is_active' => true]);

        $component = Livewire::actingAs($this->rep)
            ->test(SalesFlow::class)
            ->call('scanBarcode', 'SHARED9');

        $this->assertCount(0, $component->get('cart'));
        $this->assertStringContainsString('SHARED9', $component->get('errorMessage'));
    }

    public function test_scanning_same_barcode_twice_increments_quantity(): void
    {
        $this->product(['barcode' => 'DUP123']);

        $component = Livewire::actingAs($this->rep)
            ->test(SalesFlow::class)
            ->call('scanBarcode', 'DUP123')
            ->call('scanBarcode', 'DUP123');

        $cart = $component->get('cart');
        $this->assertCount(1, $cart);
        $this->assertSame(2.0, (float) $cart[0]['quantity']);
    }
}
