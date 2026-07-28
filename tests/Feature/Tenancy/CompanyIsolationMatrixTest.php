<?php

namespace Tests\Feature\Tenancy;

use App\Filament\Resources\StockResource;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\CustomerPolicy;
use App\Policies\StockPolicy;
use App\Services\NumberSequenceService;
use App\Support\ActiveCompanyContext;
use App\Support\ApiAbilities;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyIsolationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->user->companies()->sync([$this->companyA->id]);
    }

    public function test_missing_context_fails_closed_and_active_context_scopes_reads(): void
    {
        Customer::factory()->create(['company_id' => $this->companyA->id, 'code' => 'A-ONLY']);
        Customer::factory()->create(['company_id' => $this->companyB->id, 'code' => 'B-ONLY']);

        $context = app(ActiveCompanyContext::class);
        $context->enforce();

        $this->assertSame(0, Customer::count());

        $context->setCompanyId($this->companyA->id);

        $this->assertSame(['A-ONLY'], Customer::pluck('code')->all());
    }

    public function test_active_context_rejects_cross_company_writes(): void
    {
        app(ActiveCompanyContext::class)->setCompanyId($this->companyA->id);

        $this->expectException(AuthorizationException::class);

        Customer::factory()->create([
            'company_id' => $this->companyB->id,
            'code' => 'FORBIDDEN',
        ]);
    }

    public function test_web_entry_point_rejects_an_unassigned_active_company(): void
    {
        $this->user->assignRole('sales_rep');

        $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->get('/app')
            ->assertForbidden();
    }

    public function test_filament_record_binding_denies_foreign_company_and_allows_own_company(): void
    {
        $this->user->assignRole('sales_manager');
        $own = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $foreign = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->get("/admin/customers/{$own->id}/edit")
            ->assertOk();

        $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->get("/admin/customers/{$foreign->id}/edit")
            ->assertNotFound();
    }

    public function test_authorized_company_switch_is_logged_and_visible(): void
    {
        $this->user->companies()->attach($this->companyB->id);
        $this->user->assignRole('sales_rep');

        $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->from('/app')
            ->post('/company/switch', ['company_id' => $this->companyB->id])
            ->assertRedirect('/app')
            ->assertSessionHas('active_company_id', $this->companyB->id);

        $this->assertDatabaseHas('activities', [
            'company_id' => $this->companyB->id,
            'user_id' => $this->user->id,
            'type' => 'company_switched',
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->get('/app')
            ->assertOk()
            ->assertSee($this->companyB->name_ar);
    }

    public function test_filament_queries_follow_the_selected_secondary_company(): void
    {
        $this->user->companies()->attach($this->companyB->id);
        $this->user->assignRole('sales_manager');
        $customerA = Customer::factory()->create([
            'company_id' => $this->companyA->id,
            'route_id' => null,
            'name_en' => 'Company A Map Customer',
            'name_ar' => 'عميل شركة أ',
            'latitude' => 30.0,
            'longitude' => 31.0,
        ]);
        $customerB = app(ActiveCompanyContext::class)->runWithCompany(
            $this->companyB->id,
            fn () => Customer::factory()->create([
                'company_id' => $this->companyB->id,
                'route_id' => null,
                'name_en' => 'Company B Map Customer',
                'name_ar' => 'عميل شركة ب',
                'latitude' => 29.0,
                'longitude' => 30.0,
            ]),
        );

        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->get('/admin/customer-map');

        $response->assertOk();
        $response->assertSee($customerB->name_ar);
        $response->assertDontSee($customerA->name_ar);
    }

    public function test_service_boundary_rejects_a_company_other_than_the_active_context(): void
    {
        app(ActiveCompanyContext::class)->setCompanyId($this->companyA->id);

        $this->expectException(AuthorizationException::class);

        app(NumberSequenceService::class)->generate('sales_invoice', $this->companyB->id);
    }

    public function test_api_entry_point_returns_only_the_explicit_authorized_company(): void
    {
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);
        Sanctum::actingAs($this->user, [ApiAbilities::READ_CUSTOMERS]);

        $response = $this->withHeader('X-Jawla-Company', (string) $this->companyA->id)
            ->getJson('/api/v1/customers');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $customerA->id]);
        $response->assertJsonMissing(['id' => $customerB->id]);
    }

    public function test_api_entry_point_rejects_an_unassigned_company_header(): void
    {
        Sanctum::actingAs($this->user, [ApiAbilities::READ_CUSTOMERS]);

        $this->withHeader('X-Jawla-Company', (string) $this->companyB->id)
            ->getJson('/api/v1/customers')
            ->assertForbidden();
    }

    public function test_policy_denies_view_on_foreign_company_record(): void
    {
        $this->user->assignRole('sales_manager');
        $foreign = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $policy = app(CustomerPolicy::class);

        $this->assertFalse($policy->view($this->user, $foreign));
    }

    public function test_policy_allows_view_on_own_company_record(): void
    {
        $this->user->assignRole('sales_manager');
        $own = Customer::factory()->create(['company_id' => $this->companyA->id]);

        $policy = app(CustomerPolicy::class);

        $this->assertTrue($policy->view($this->user, $own));
    }

    public function test_policy_denies_update_on_foreign_company_record(): void
    {
        $this->user->assignRole('sales_manager');
        $foreign = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $policy = app(CustomerPolicy::class);

        $this->assertFalse($policy->update($this->user, $foreign));
    }

    public function test_policy_denies_delete_on_foreign_company_record(): void
    {
        $this->user->assignRole('sales_manager');
        $foreign = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $policy = app(CustomerPolicy::class);

        $this->assertFalse($policy->delete($this->user, $foreign));
    }

    public function test_policies_follow_the_selected_secondary_company(): void
    {
        $this->user->companies()->attach($this->companyB->id);
        $this->user->assignRole('admin');

        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = app(ActiveCompanyContext::class)->runWithCompany(
            $this->companyB->id,
            fn () => Customer::factory()->create([
                'company_id' => $this->companyB->id,
                'route_id' => null,
            ]),
        );

        app(ActiveCompanyContext::class)->setCompanyId($this->companyB->id);

        $policy = app(CustomerPolicy::class);
        $this->assertTrue($policy->view($this->user, $customerB));
        $this->assertFalse($policy->view($this->user, $customerA));
    }

    public function test_stock_policy_and_resource_require_active_company_ownership(): void
    {
        $this->user->companies()->attach($this->companyB->id);
        $this->user->assignRole('admin');

        $stockA = $this->makeStockForCompany($this->companyA, 'A-STOCK');
        $stockB = $this->makeStockForCompany($this->companyB, 'B-STOCK');

        app(ActiveCompanyContext::class)->setCompanyId($this->companyB->id);
        $this->actingAs($this->user);

        $policy = app(StockPolicy::class);
        $this->assertTrue($policy->view($this->user, $stockB));
        $this->assertTrue($policy->adjust($this->user, $stockB));
        $this->assertFalse($policy->view($this->user, $stockA));
        $this->assertFalse($policy->adjust($this->user, $stockA));

        $this->assertSame(
            [$stockB->id],
            StockResource::getEloquentQuery()->orderBy('id')->pluck('id')->all(),
        );
    }

    private function makeStockForCompany(Company $company, string $sku): Stock
    {
        return app(ActiveCompanyContext::class)->runWithCompany(
            $company->id,
            function () use ($company, $sku): Stock {
                $category = ProductCategory::factory()->create(['company_id' => $company->id]);
                $product = Product::factory()->create([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'sku' => $sku,
                ]);
                $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

                return Stock::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'batch_id' => null,
                    'quantity' => 10,
                ]);
            },
        );
    }
}
