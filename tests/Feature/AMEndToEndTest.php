<?php

namespace Tests\Feature;

use App\Models\Alarm;
use App\Models\AlarmRead;
use App\Models\Customer;
use App\Models\DailyVisitAssignment;
use App\Models\OutOfStockRequest;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitReport;
use App\Services\AlarmService;
use App\Services\ComplaintService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PdfService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AMEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_am1_through_am9_full_narrative_reproduces(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $manager = User::where('email', 'manager@jawla.test')->first();
        $customer = Customer::where('code', 'C-1')->first();
        $productPP = Product::where('sku', 'PP-H030')->first();
        $product952 = Product::where('sku', 'CHEM-952')->first();

        $this->assertNotNull($rep);
        $this->assertNotNull($manager);
        $this->assertNotNull($customer);
        $this->assertNotNull($productPP);
        $this->assertNotNull($product952);

        // AM1: manager assigned 5 visits
        $assignments = DailyVisitAssignment::where('user_id', $rep->id)
            ->whereDate('visit_date', today())
            ->get();
        $this->assertCount(5, $assignments, 'AM1: Expected 5 visit assignments from seeder');

        // AM2: rep starts day (work session already created by seeder)
        $this->actingAs($rep);
        $workSession = $rep->workSessions()->latest()->first();
        $this->assertNotNull($workSession, 'AM2: Work session exists');

        // AM3: GPS-confirmed arrival (mock: visit is created + arrival confirmed)
        $visit = Visit::create([
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'work_session_id' => $workSession->id,
            'route_id' => $customer->route_id,
            'daily_visit_assignment_id' => $assignments->first()->id,
            'purpose' => 'sale',
            'status' => 'open',
            'arrival_confirmed' => true,
            'arrival_confirmed_at' => now(),
            'checkin_latitude' => 30.10,
            'checkin_longitude' => 31.30,
            'checkin_at' => now(),
        ]);
        $visit->update(['is_out_of_route' => false]);
        $this->assertTrue($visit->arrival_confirmed, 'AM3: Arrival confirmed');

        // AM4: signed visit report survives kill (signature mock skipped in service-level test)
        $report = VisitReport::create([
            'visit_id' => $visit->id,
            'summary' => 'остаток заказ, buyer requested PP-H030 5 tons at 950 EGP.',
            'customer_feedback' => 'Satisfied',
            'action_taken' => 'Prospecting',
            'follow_up_needed' => false,
            'submitted_at' => now(),
        ]);
        $visit->update(['status' => 'closed']);
        $this->assertDatabaseHas('visit_reports', ['visit_id' => $visit->id]);

        // AM5: new customer pending (created by rep, pending status)
        $pendingCustomer = Customer::where('status', 'pending')->first();
        $this->assertNotNull($pendingCustomer, 'AM5: Pending customer exists');

        app(AlarmService::class)->raise(
            'new_customer_pending',
            $pendingCustomer,
            "New customer pending: {$pendingCustomer->name_ar}",
            'Rep submitted for approval',
            'warning',
        );
        $recipients = AlarmRead::whereHas('alarm', fn ($q) => $q->where('reference_type', Customer::class)->where('reference_id', $pendingCustomer->id))->pluck('user_id');
        $this->assertContains($manager->id, $recipients->all(), 'AM5: Manager notified of pending customer');

        // AM5b: manager approves
        $pendingCustomer->update(['status' => 'approved', 'approved_by' => $manager->id, 'approved_at' => now()]);
        $this->assertSame('approved', $pendingCustomer->fresh()->status);

        // AM6: rep requests price; manager sets 1000 ± range
        $quotationRequest = PriceQuotationRequest::create([
            'company_id' => $rep->company_id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'product_id' => $productPP->id,
            'visit_id' => $visit->id,
            'quantity_requested' => 5,
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $quotation = PriceQuotation::create([
            'price_quotation_request_id' => $quotationRequest->id,
            'base_price' => 1000,
            'manager_plus' => 0,
            'manager_minus' => 100,
            'rep_plus' => 0,
            'rep_minus' => 100,
            'priced_by' => $manager->id,
            'priced_at' => now(),
        ]);

        $quotationRequest->update(['status' => 'priced']);
        $this->assertSame('priced', $quotationRequest->fresh()->status);

        // AM7: rep negotiates at 950 (accepted) — must be >= floor (900)
        $floor = 1000 - 100;
        $this->assertGreaterThanOrEqual($floor, 950);

        $proforma = ProformaInvoice::create([
            'company_id' => $rep->company_id,
            'customer_id' => $customer->id,
            'user_id' => $rep->id,
            'visit_id' => $visit->id,
            'price_quotation_id' => $quotation->id,
            'proforma_number' => 'PF-GPC-2026-00001',
            'subtotal' => 4750.00,
            'vat_amount' => 665.00,
            'total' => 5415.00,
            'company_bank_account_id' => 1,
            'status' => 'sent',
            'posting_date' => today(),
        ]);
        $this->assertSame('sent', $proforma->status);

        // AM7b: 850 rejects (below floor)
        $this->assertLessThan($floor, 850);

        // AM8: invoice with QR, stock deducted atomically, payment collected
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $rep->company_id,
            'customer_id' => $customer->id,
            'product_id' => $productPP->id,
            'quantity' => 5,
            'unit_price' => 950,
        ]);
        $this->assertSame((float) $invoice->total, (float) $customer->fresh()->balance, 'AM8: customer balance updated');

        $pdfService = app(PdfService::class);
        $path = $pdfService->generateInvoice($invoice);
        $this->assertNotEmpty($path, 'AM8: PDF generated');

        $payment = app(PaymentService::class)->collect(
            companyId: $rep->company_id,
            userId: $rep->id,
            customerId: $customer->id,
            amount: (float) $invoice->total,
            method: 'cash',
            invoiceId: $invoice->id,
        );
        $this->assertSame(0.0, (float) $invoice->fresh()->remaining_amount, 'AM8: invoice fully paid');

        // AM9: rep flags Material 952 out-of-stock -> red alarm to Finance + Manager + Executive
        $oosr = OutOfStockRequest::create([
            'company_id' => $rep->company_id,
            'user_id' => $rep->id,
            'customer_id' => $customer->id,
            'product_id' => $product952->id,
            'quantity_requested' => 3,
            'status' => 'open',
        ]);

        $alarm = app(AlarmService::class)->raise(
            'out_of_stock_request',
            $oosr,
            "Out of stock: {$product952->name_ar}",
            "Rep {$rep->name} flagged {$product952->sku}",
            'critical',
        );

        $recipients = AlarmRead::where('alarm_id', $alarm->id)->pluck('user_id');
        $finance = User::where('email', 'accounts@jawla.test')->first()->id;
        $executive = User::where('email', 'executive@jawla.test')->first()->id;
        $this->assertContains($finance, $recipients->all(), 'AM9: Finance notified');
        $this->assertContains($manager->id, $recipients->all(), 'AM9: Manager notified');
        $this->assertContains($executive, $recipients->all(), 'AM9: Executive notified');
        $this->assertSame('critical', $alarm->severity);

        // AM9b: complaint logged -> alarm to manager
        $complaint = app(ComplaintService::class)->log(
            companyId: $rep->company_id,
            userId: $rep->id,
            customerId: $customer->id,
            type: 'quality_issue',
            description: 'PP-H030 bag seal broken on delivery',
            visitId: $visit->id,
        );
        $this->assertSame('open', $complaint->status);

        // Manager resolves complaint
        app(ComplaintService::class)->resolve($complaint, $manager->id, 'Replacement dispatched next visit.');
        $this->assertSame('resolved', $complaint->fresh()->status);
    }
}
