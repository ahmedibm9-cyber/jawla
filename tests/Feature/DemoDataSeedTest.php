<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RED test: DemoSeeder must produce transactional demo data
 * so that the admin dashboard and all registers show real numbers.
 */
class DemoDataSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_provides_a_complete_client_evaluation_dataset(): void
    {
        config(['app.demo_seed_showcase' => true]);
        $this->seed(DemoSeeder::class);

        $this->assertGreaterThanOrEqual(2, Company::count(), 'DemoSeeder must demonstrate multi-company access');
        $this->assertGreaterThanOrEqual(50, Product::count(), 'DemoSeeder must seed a realistic product catalogue');
        $this->assertGreaterThanOrEqual(50, Customer::where('status', 'approved')->count(), 'DemoSeeder must seed a realistic approved customer base');
        $this->assertGreaterThanOrEqual(100, Invoice::count(), 'DemoSeeder must seed enough invoice history for dashboards and reports');
        $this->assertGreaterThanOrEqual(100, Visit::count(), 'DemoSeeder must seed enough visit history for route and performance views');

        $this->assertGreaterThan(0, Payment::count(), 'DemoSeeder must seed payments');
        $this->assertGreaterThan(0, PurchaseOrder::count(), 'DemoSeeder must seed purchase orders');
        $this->assertGreaterThan(0, Alarm::count(), 'DemoSeeder must seed alarms');
        $this->assertGreaterThan(0, PriceQuotationRequest::count(), 'DemoSeeder must seed quotation requests');
        $this->assertGreaterThan(0, ReturnRecord::count(), 'DemoSeeder must seed returns');
        $this->assertGreaterThan(0, Visit::whereNotNull('checkin_at')->count(), 'DemoSeeder must seed completed visits');
        $this->assertGreaterThan(0, Expense::count(), 'DemoSeeder must seed expenses');

        $this->assertTrue(User::where('email', 'hr@jawla.test')->firstOrFail()->hasRole('hr_admin'));
        $this->assertTrue(User::where('email', 'viewer@jawla.test')->firstOrFail()->hasRole('system_viewer'));
        $this->assertFalse(User::where('email', 'disabled@jawla.test')->firstOrFail()->is_active);
        $this->assertGreaterThanOrEqual(2, User::where('email', 'admin@jawla.test')->firstOrFail()->companies()->count());
    }
}
