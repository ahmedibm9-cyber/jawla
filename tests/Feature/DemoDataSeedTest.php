<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PriceQuotationRequest;
use App\Models\PurchaseOrder;
use App\Models\ReturnRecord;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_demo_seeder_populates_invoices(): void
    {
        $count = Invoice::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one invoice');
    }

    public function test_demo_seeder_populates_payments(): void
    {
        $count = Payment::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one payment');
    }

    public function test_demo_seeder_populates_purchase_orders(): void
    {
        $count = PurchaseOrder::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one purchase order');
    }

    public function test_demo_seeder_populates_alarms(): void
    {
        $count = Alarm::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one alarm');
    }

    public function test_demo_seeder_populates_quotations(): void
    {
        $count = PriceQuotationRequest::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one quotation request');
    }

    public function test_demo_seeder_populates_returns(): void
    {
        $count = ReturnRecord::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one return');
    }

    public function test_demo_seeder_populates_visits_with_checkins(): void
    {
        $count = Visit::whereNotNull('checkin_at')->count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one completed visit');
    }

    public function test_demo_seeder_populates_expenses(): void
    {
        $count = Expense::count();
        $this->assertGreaterThan(0, $count, 'DemoSeeder must seed at least one expense');
    }
}
