<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\AlarmRead;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\OutOfStockRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\AlarmService;
use App\Services\ComplaintService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlarmBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_logs_complaint_and_broadcasts_alarm_to_sales_manager(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $customer = Customer::where('status', 'approved')->first();

        $service = app(ComplaintService::class);

        $complaint = $service->log(
            companyId: $rep->company_id,
            userId: $rep->id,
            customerId: $customer->id,
            type: 'quality_issue',
            description: 'Bag seal broken on PP-H030',
        );

        $this->assertSame('open', $complaint->status);

        $alarm = Alarm::where('reference_type', Complaint::class)
            ->where('reference_id', $complaint->id)
            ->first();

        $this->assertNotNull($alarm);
        $this->assertSame('customer_complaint', $alarm->type);
        $this->assertSame('critical', $alarm->severity);

        $recipients = AlarmRead::where('alarm_id', $alarm->id)->pluck('user_id');
        $manager = User::where('email', 'manager@jawla.test')->first()->id;
        $this->assertContains($manager, $recipients->all());
        $this->assertCount(1, $recipients);
    }

    public function test_out_of_stock_alarm_broadcasts_to_three_roles(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $customer = Customer::where('status', 'approved')->first();
        $product = Product::where('sku', 'CHEM-952')->first();

        $oosr = OutOfStockRequest::create([
            'company_id' => $rep->company_id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity_requested' => 5,
            'status' => 'open',
        ]);

        $alarm = app(AlarmService::class)->raise(
            'out_of_stock_request',
            $oosr,
            "Out of stock: {$product->name_ar}",
            "Rep {$rep->name} flagged {$product->sku}",
            'critical',
        );

        $recipients = AlarmRead::where('alarm_id', $alarm->id)->pluck('user_id');
        $finance = User::where('email', 'accounts@jawla.test')->first()->id;
        $manager = User::where('email', 'manager@jawla.test')->first()->id;
        $executive = User::where('email', 'executive@jawla.test')->first()->id;

        $this->assertContains($finance, $recipients->all());
        $this->assertContains($manager, $recipients->all());
        $this->assertContains($executive, $recipients->all());
        $this->assertCount(3, $recipients);
    }

    public function test_new_pending_customer_alarm_targets_sales_manager_only(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();

        $customer = Customer::create([
            'company_id' => $rep->company_id,
            'code' => 'C-TEST',
            'name_ar' => 'عميل اختبار',
            'name_en' => 'Test Customer',
            'phone' => '01000000000',
            'status' => 'pending',
            'added_by' => $rep->id,
            'is_active' => true,
        ]);

        $alarm = app(AlarmService::class)->raise(
            'new_customer_pending',
            $customer,
            "New customer pending: {$customer->name_ar}",
            "Rep {$rep->name} submitted new customer for approval",
            'warning',
        );

        $recipients = AlarmRead::where('alarm_id', $alarm->id)->pluck('user_id');
        $manager = User::where('email', 'manager@jawla.test')->first()->id;

        $this->assertContains($manager, $recipients->all());
        $this->assertCount(1, $recipients);
    }
}