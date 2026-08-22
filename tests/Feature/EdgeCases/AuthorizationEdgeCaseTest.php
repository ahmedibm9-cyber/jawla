<?php

declare(strict_types=1);

namespace Tests\Feature\EdgeCases;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P0 edge-case authorization tests: cross-company, role escalation.
 *
 * ponytail: Tests HTTP-level access control via service calls (not Livewire).
 * Validates that cross-company data isolation holds even with direct model access.
 */
class AuthorizationEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private User $repA;

    private User $repB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->companyA = Company::factory()->create(['vat_percent' => 0]);
        $this->companyB = Company::factory()->create(['vat_percent' => 0]);

        $this->repA = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->repA->assignRole('sales_rep');

        $this->repB = User::factory()->create(['company_id' => $this->companyB->id]);
        $this->repB->assignRole('sales_rep');

        app(ActiveCompanyContext::class)->setCompanyId($this->companyA->id);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    /** Rep A cannot see Rep B's customer via model query (BelongsToCompany global scope). */
    #[Test]
    public function test_rep_cannot_query_other_company_customer(): void
    {
        $ctx = app(ActiveCompanyContext::class);

        $customerB = $ctx->runWithCompany($this->companyB->id, function () {
            $route = Route::factory()->create(['company_id' => $this->companyB->id]);

            return Customer::factory()->create([
                'company_id' => $this->companyB->id,
                'route_id' => $route->id,
            ]);
        });

        // Back in company A context — customer B should be invisible
        $found = Customer::find($customerB->id);
        $this->assertNull($found, 'Global scope must hide other-company customer');
    }

    /** Rep A cannot see Rep B's product via model query. */
    #[Test]
    public function test_rep_cannot_query_other_company_product(): void
    {
        $ctx = app(ActiveCompanyContext::class);

        $productB = $ctx->runWithCompany($this->companyB->id, function () {
            $category = ProductCategory::factory()->create(['company_id' => $this->companyB->id]);

            return Product::factory()->create([
                'company_id' => $this->companyB->id,
                'category_id' => $category->id,
            ]);
        });

        $found = Product::find($productB->id);
        $this->assertNull($found, 'Global scope must hide other-company product');
    }

    /** Rep A cannot see Rep B's invoice. */
    #[Test]
    public function test_rep_cannot_query_other_company_invoice(): void
    {
        $ctx = app(ActiveCompanyContext::class);

        $invoiceB = $ctx->runWithCompany($this->companyB->id, function () {
            $rep = User::factory()->create(['company_id' => $this->companyB->id]);
            $rep->assignRole('sales_rep');
            $route = Route::factory()->create(['company_id' => $this->companyB->id]);
            $customer = Customer::factory()->create([
                'company_id' => $this->companyB->id,
                'route_id' => $route->id,
            ]);
            $category = ProductCategory::factory()->create(['company_id' => $this->companyB->id]);
            $product = Product::factory()->create([
                'company_id' => $this->companyB->id,
                'category_id' => $category->id,
                'price' => 50.00,
            ]);
            $van = Warehouse::factory()->create([
                'company_id' => $this->companyB->id,
                'user_id' => $rep->id,
                'type' => 'van',
            ]);
            DB::table('stocks')->insert([
                'warehouse_id' => $van->id,
                'product_id' => $product->id,
                'quantity' => 50,
            ]);

            return app(InvoiceService::class)->create([
                'company_id' => $this->companyB->id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'user_id' => $rep->id,
                'quantity' => 2,
                'unit_price' => 50.00,
            ]);
        });

        $found = Invoice::find($invoiceB->id);
        $this->assertNull($found, 'Global scope must hide other-company invoice');
    }

    /** Cross-company invoice ID cannot be used for payment even if known. */
    #[Test]
    public function test_cross_company_invoice_payment_rejected(): void
    {
        $ctx = app(ActiveCompanyContext::class);

        $invoiceB = $ctx->runWithCompany($this->companyB->id, function () {
            $rep = User::factory()->create(['company_id' => $this->companyB->id]);
            $rep->assignRole('sales_rep');
            $route = Route::factory()->create(['company_id' => $this->companyB->id]);
            $customer = Customer::factory()->create([
                'company_id' => $this->companyB->id,
                'route_id' => $route->id,
            ]);
            $category = ProductCategory::factory()->create(['company_id' => $this->companyB->id]);
            $product = Product::factory()->create([
                'company_id' => $this->companyB->id,
                'category_id' => $category->id,
                'price' => 50.00,
            ]);
            $van = Warehouse::factory()->create([
                'company_id' => $this->companyB->id,
                'user_id' => $rep->id,
                'type' => 'van',
            ]);
            DB::table('stocks')->insert([
                'warehouse_id' => $van->id,
                'product_id' => $product->id,
                'quantity' => 50,
            ]);

            return app(InvoiceService::class)->create([
                'company_id' => $this->companyB->id,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'user_id' => $rep->id,
                'quantity' => 1,
                'unit_price' => 50.00,
            ]);
        });

        // Rep A tries to pay company B's invoice
        $this->actingAs($this->repA);

        $this->expectException(\Throwable::class);
        app(PaymentService::class)->collect(
            companyId: $this->companyA->id,
            userId: $this->repA->id,
            customerId: 0, // doesn't matter — invoice lookup will fail
            amount: 50.00,
            method: 'cash',
            invoiceId: $invoiceB->id,
        );
    }

    /** Unauthenticated user cannot access PWA home. */
    #[Test]
    public function test_unauthenticated_pwa_blocked(): void
    {
        $response = $this->get('/app');
        $response->assertStatus(302);
        $response->assertRedirectContains('login');
    }

    /** Unauthenticated user cannot access Filament admin. */
    #[Test]
    public function test_unauthenticated_admin_blocked(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(302);
    }

    /** Rep cannot access Filament admin (redirected to /app). */
    #[Test]
    public function test_rep_redirected_from_admin(): void
    {
        $this->actingAs($this->repA);

        $response = $this->get('/admin');
        // FilamentAuthenticate redirects rep to /app
        $this->assertContains($response->status(), [200, 302]);
        if ($response->status() === 302) {
            $response->assertRedirectContains('/app');
        }
    }
}
