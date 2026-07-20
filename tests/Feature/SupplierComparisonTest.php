<?php

namespace Tests\Feature;

use App\Filament\Pages\SupplierComparison;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierComparisonTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['company_id' => $this->company->id]);
        $u->assignRole($role);

        return $u;
    }

    private function offer(Product $product, Supplier $supplier, float $price, string $status = 'pending'): PurchaseRequest
    {
        return PurchaseRequest::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user('rep')->id,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'quantity' => 10,
            'offered_price' => $price,
            'currency' => 'EGP',
            'status' => $status,
        ]);
    }

    public function test_only_purchasing_roles_can_access(): void
    {
        $this->assertTrue($this->actingAsAndCheck('admin'));
        $this->assertTrue($this->actingAsAndCheck('purchasing'));
        $this->assertTrue($this->actingAsAndCheck('sales_manager'));
        $this->assertFalse($this->actingAsAndCheck('rep'));
        $this->assertFalse($this->actingAsAndCheck('warehouse_keeper'));
    }

    private function actingAsAndCheck(string $role): bool
    {
        $this->actingAs($this->user($role));

        return SupplierComparison::canAccess();
    }

    public function test_groups_offers_by_product_sorted_cheapest_first(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'منتج']);
        $s1 = Supplier::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'مورد غالي']);
        $s2 = Supplier::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'مورد رخيص']);

        $this->offer($product, $s1, 300);
        $this->offer($product, $s2, 200);

        $this->actingAs($this->user('purchasing'));
        $page = Livewire::test(SupplierComparison::class);

        $groups = $page->instance()->groups;
        $this->assertCount(1, $groups);
        $group = $groups->first();
        $this->assertSame(200.0, (float) $group['offers']->first()->offered_price, 'cheapest first');
        $this->assertSame(300.0, (float) $group['offers']->last()->offered_price);
    }

    public function test_excludes_closed_offers_and_renders_open_ones(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'منتج مفتوح']);
        $supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'name_ar' => 'مورد']);
        $this->offer($product, $supplier, 150, 'pending');
        $this->offer($product, $supplier, 999, 'purchasing_approved'); // closed → excluded

        $this->actingAs($this->user('purchasing'));
        $page = Livewire::test(SupplierComparison::class)->assertOk()->assertSee('منتج مفتوح');

        $offers = $page->instance()->groups->first()['offers'];
        $this->assertCount(1, $offers);
        $this->assertSame(150.0, (float) $offers->first()->offered_price);
    }

    public function test_empty_state_when_no_open_offers(): void
    {
        $this->actingAs($this->user('purchasing'));
        Livewire::test(SupplierComparison::class)->assertOk();
        $this->assertCount(0, Livewire::test(SupplierComparison::class)->instance()->groups);
    }
}
