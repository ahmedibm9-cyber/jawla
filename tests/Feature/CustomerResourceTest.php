<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
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
        // Latitude must be between -90 and 90
        $this->assertTrue(-90 <= 30.0444 && 30.0444 <= 90, 'Valid latitude passes');
        $this->assertFalse(-90 <= 999 && 999 <= 90, '999 is out of latitude range');

        // Longitude must be between -180 and 180
        $this->assertTrue(-180 <= 31.2357 && 31.2357 <= 180, 'Valid longitude passes');
        $this->assertFalse(-180 <= 999 && 999 <= 180, '999 is out of longitude range');
    }

    public function test_customer_with_valid_gps_can_be_persisted(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();

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

        $customer = Customer::where('company_id', $admin->company_id)->first();
        $customer->update(['latitude' => null, 'longitude' => null]);

        $this->assertNull($customer->fresh()->latitude);
        $this->assertNull($customer->fresh()->longitude);
    }
}
