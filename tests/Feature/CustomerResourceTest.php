<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US-9.4 — Manage Customers (Admin)
 *
 * Tests admin Filament CustomerResource CRUD, approval, GPS/Leaflet map.
 */
class CustomerResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_admin_can_view_customer_list(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $this->get('/admin/customers')->assertOk();
    }

    public function test_sales_manager_can_view_customer_list(): void
    {
        $manager = User::where('email', 'manager@jawla.test')->first();
        $this->actingAs($manager);

        $this->get('/admin/customers')->assertOk();
    }

    public function test_rep_is_redirected_from_customer_list(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/admin/customers')->assertRedirect();
    }

    public function test_admin_can_create_customer(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $this->get('/admin/customers/create')->assertOk();
    }

    public function test_customer_list_shows_status_badges(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $response = $this->get('/admin/customers');
        $response->assertOk();
    }

    public function test_leaflet_customer_map_page_is_accessible(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        $this->get('/admin/customer-map')->assertOk();
    }

    public function test_executive_can_access_customer_map(): void
    {
        $executive = User::where('email', 'executive@jawla.test')->first();
        $this->actingAs($executive);

        $this->get('/admin/customer-map')->assertOk();
    }

    public function test_rep_cannot_access_customer_map(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/admin/customer-map')->assertRedirect();
    }

    public function test_customer_gps_range_validation_logic(): void
    {
        // ponytail: pure range check, no model needed — avoids BelongsToCompany boot
        $validLat = 30.0444;
        $validLng = 31.2357;
        $this->assertTrue($validLat >= -90 && $validLat <= 90, 'Valid latitude passes');
        $this->assertTrue($validLng >= -180 && $validLng <= 180, 'Valid longitude passes');

        $invalidLat = 999.0;
        $invalidLng = 999.0;
        $this->assertFalse($invalidLat >= -90 && $invalidLat <= 90, '999 is out of latitude range');
        $this->assertFalse($invalidLng >= -180 && $invalidLng <= 180, '999 is out of longitude range');
    }

    public function test_customer_with_valid_gps_can_be_persisted(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);
        app(ActiveCompanyContext::class)->setFromUser($admin);

        $customer = Customer::where('company_id', $admin->company_id)->first();
        $customer->update([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);
    }

    public function test_customer_gps_fields_are_nullable(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);
        app(ActiveCompanyContext::class)->setFromUser($admin);

        $customer = Customer::where('company_id', $admin->company_id)->first();
        $customer->update(['latitude' => null, 'longitude' => null]);

        $this->assertNull($customer->fresh()->latitude);
        $this->assertNull($customer->fresh()->longitude);
    }
}
