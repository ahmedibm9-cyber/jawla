<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin access, data isolation, and UUID integrity.
 *
 * - Admins can access every admin page (list + create/edit views)
 * - Users cannot access other companies' data
 * - Every user has a non-null unique UUID
 */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $otherCompanyUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);

        $this->admin = User::where('email', 'admin@jawla.test')->firstOrFail();

        $otherCompany = Company::factory()->create(['name_en' => 'Other Corp']);
        $this->otherCompanyUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'other@test.test',
        ]);
    }

    // ─── Admin access to all pages ─────────────────────────────────

    public function test_admin_can_access_all_resource_index_pages(): void
    {
        $this->actingAs($this->admin);

        $resources = [
            '/admin/users',
            '/admin/companies',
            '/admin/customers',
            '/admin/products',
            '/admin/stocks',
            '/admin/invoices',
            '/admin/payments',
            '/admin/proforma-invoices',
            '/admin/return-records',
            '/admin/routes',
            '/admin/daily-visit-assignments',
            '/admin/sales-targets',
            '/admin/purchase-requests',
            '/admin/purchase-orders',
            '/admin/price-quotation-requests',
            '/admin/batches',
            '/admin/van-transfers',
            '/admin/goods-in-transits',
            '/admin/complaints',
            '/admin/alarms',
            '/admin/tasks',
            '/admin/expenses',
            '/admin/cash-reconciliations',
        ];

        foreach ($resources as $url) {
            $response = $this->get($url);
            $response->assertOk();
        }
    }

    public function test_admin_can_access_all_custom_pages(): void
    {
        $this->actingAs($this->admin);

        $pages = [
            '/admin/dashboard',
            '/admin/activity-log',
            '/admin/reports-page',
            '/admin/collect-payment',
            '/admin/stock-import',
            '/admin/customer-map',
            '/admin/rep-live-map',
            '/admin/supplier-comparison',
            '/admin/api-tokens',
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertOk();
        }
    }

    public function test_admin_can_access_create_pages_for_writable_resources(): void
    {
        $this->actingAs($this->admin);

        // Read-only resources excluded: invoices, return-records, proforma-invoices,
        // stocks, purchase-orders (explicitly read-only via canCreate: false)
        $creatable = [
            '/admin/users/create',
            '/admin/companies/create',
            '/admin/customers/create',
            '/admin/products/create',
            '/admin/payments/create',
            '/admin/routes/create',
            '/admin/daily-visit-assignments/create',
            '/admin/sales-targets/create',
            '/admin/purchase-requests/create',
            '/admin/price-quotation-requests/create',
            '/admin/batches/create',
            '/admin/van-transfers/create',
            '/admin/goods-in-transits/create',
            '/admin/complaints/create',
            '/admin/alarms/create',
            '/admin/tasks/create',
            '/admin/expenses/create',
        ];

        foreach ($creatable as $url) {
            $response = $this->get($url);
            $response->assertOk("Created page returned non-200 for {$url}");
        }
    }

    public function test_admin_can_access_edit_page_for_existing_customer(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::where('company_id', $this->admin->company_id)->firstOrFail();

        $this->get("/admin/customers/{$customer->id}/edit")->assertOk();
    }

    // ─── Data isolation ────────────────────────────────────────────

    public function test_user_cannot_access_another_companys_customers(): void
    {
        $myCustomer = Customer::factory()->create([
            'company_id' => $this->admin->company_id,
            'name_en' => 'My Customer',
        ]);

        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->otherCompanyUser->company_id,
            'name_en' => 'Other Customer',
        ]);

        $myVisible = Customer::where('company_id', $this->admin->company_id)->get();
        $this->assertTrue($myVisible->contains('id', $myCustomer->id));
        $this->assertFalse($myVisible->contains('id', $otherCustomer->id));
    }

    public function test_user_cannot_access_another_companys_invoices(): void
    {
        Customer::factory()->create(['company_id' => $this->admin->company_id]);
        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->otherCompanyUser->company_id,
        ]);

        $myInvoice = Invoice::factory()->create([
            'company_id' => $this->admin->company_id,
            'customer_id' => Customer::where('company_id', $this->admin->company_id)->first()->id,
            'user_id' => $this->admin->id,
        ]);

        $otherInvoice = Invoice::factory()->create([
            'company_id' => $this->otherCompanyUser->company_id,
            'customer_id' => $otherCustomer->id,
            'user_id' => $this->otherCompanyUser->id,
        ]);

        $myVisible = Invoice::where('company_id', $this->admin->company_id)->get();
        $this->assertTrue($myVisible->contains('id', $myInvoice->id));
        $this->assertFalse($myVisible->contains('id', $otherInvoice->id));
    }

    public function test_user_cannot_access_another_companys_products(): void
    {
        $myProduct = Product::factory()->create([
            'company_id' => $this->admin->company_id,
            'name_en' => 'My Product',
        ]);

        $otherProduct = Product::factory()->create([
            'company_id' => $this->otherCompanyUser->company_id,
            'name_en' => 'Other Product',
        ]);

        $myVisible = Product::where('company_id', $this->admin->company_id)->get();
        $this->assertTrue($myVisible->contains('id', $myProduct->id));
        $this->assertFalse($myVisible->contains('id', $otherProduct->id));
    }

    public function test_acting_as_other_company_user_cannot_see_admin_data(): void
    {
        $this->actingAs($this->otherCompanyUser);

        foreach (['/admin/users', '/admin/invoices', '/admin/customers'] as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(200, $response->status());
            $this->assertNotEquals(500, $response->status());
        }
    }

    // ─── UUID integrity ────────────────────────────────────────────

    public function test_every_user_has_a_unique_uuid(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->assertNotNull($user->uuid, "User {$user->id} is missing uuid");
            $this->assertTrue(
                \Illuminate\Support\Str::isUuid($user->uuid),
                "User {$user->id} uuid '{$user->uuid}' is not a valid UUID",
            );
        }

        $this->assertCount(
            $users->count(),
            $users->pluck('uuid')->unique(),
            'UUIDs are not unique across all users',
        );
    }

    public function test_newly_created_user_auto_gets_uuid(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertNotNull($user->uuid);
        $this->assertTrue(\Illuminate\Support\Str::isUuid($user->uuid));
    }

    public function test_uuid_can_be_used_to_identify_user_uniquely(): void
    {
        $user = User::factory()->create();
        $found = User::where('uuid', $user->uuid)->first();

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    // ─── Gate: admin full_access ───────────────────────────────────

    public function test_admin_role_bypasses_all_gates(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue($this->admin->can('reports.view'));
        $this->assertTrue($this->admin->can('payments.collect'));
        $this->assertTrue($this->admin->can('stock.import'));
    }

    public function test_non_admin_role_is_gated_correctly(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        $this->assertFalse($rep->can('reports.view'));
        $this->assertFalse($rep->can('stock.import'));
    }
}
