<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Models\Alarm;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PriceQuotationRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\ReturnItem;
use App\Models\ReturnRecord;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Visit;
use App\Models\Warehouse;
use App\Models\WorkSession;
use App\Services\Contracts\StockService;
use App\Services\NumberSequenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTransactionsCommand extends Command
{
    protected $signature = 'app:seed-transactions';

    protected $description = 'Seed transactional demo data (invoices, payments, POs, etc.)';

    public function handle(): int
    {
        fwrite(STDERR, "[seed-transactions] Starting...\n");
        fwrite(STDERR, "[seed-transactions] Company check...\n");

        $company = Company::where('name_en', 'Global Plastic Company (GPC)')->first();
        if (! $company) {
            fwrite(STDERR, "[seed-transactions] ERROR: Company not found!\n");
            $this->error('Company not found. Run DemoSeeder first.');

            return 1;
        }

        // Skip if already seeded
        if (Invoice::where('company_id', $company->id)->exists()) {
            fwrite(STDERR, "[seed-transactions] Already seeded — skipping.\n");
            $this->info('Transactional data already seeded — skipping.');

            return 0;
        }

        fwrite(STDERR, "[seed-transactions] Seeding began...\n");

        $stockService = app(StockService::class);
        $names = app(NumberSequenceService::class);

        $products = Product::where('company_id', $company->id)->get();
        $customers = Customer::where('company_id', $company->id)->get();
        $rep1 = User::where('email', 'rep@jawla.test')->first();
        $rep2 = User::where('email', 'rep2@jawla.test')->first();
        $van1 = Warehouse::where('user_id', $rep1->id)->where('type', 'van')->first();
        $van2 = Warehouse::where('user_id', $rep2->id)->where('type', 'van')->first();

        if ($products->isEmpty() || $customers->isEmpty()) {
            $this->error('Products or customers not found. Run DemoSeeder first.');

            return 1;
        }

        DB::transaction(function () use ($company, $products, $customers, $stockService, $names, $rep1, $rep2, $van1, $van2) {
            $this->info('Seeding transactional demo data...');

            // Suppliers
            Supplier::firstOrCreate(['code' => 'SUP-001'], ['company_id' => $company->id, 'name_ar' => 'سابك', 'name_en' => 'SABIC', 'type' => 'international', 'contact_person' => 'Ahmed Al-Rashid', 'phone' => '+966500000001', 'email' => 'sales@sabic.com', 'payment_terms' => 'LC 90 days']);
            Supplier::firstOrCreate(['code' => 'SUP-002'], ['company_id' => $company->id, 'name_ar' => 'بروج', 'name_en' => 'Borouge', 'type' => 'international', 'contact_person' => 'Fatima Al-Mansoori', 'phone' => '+971500000002', 'email' => 'orders@borouge.com', 'payment_terms' => 'LC 60 days']);
            Supplier::firstOrCreate(['code' => 'SUP-003'], ['company_id' => $company->id, 'name_ar' => 'إكسون موبيل', 'name_en' => 'ExxonMobil Chemical', 'type' => 'international', 'contact_person' => 'John Mitchell', 'phone' => '+12810000003', 'email' => 'polyolefins@exxonmobil.com', 'payment_terms' => 'TT 30 days']);
            Supplier::firstOrCreate(['code' => 'SUP-004'], ['company_id' => $company->id, 'name_ar' => 'جولدن باور للكيماويات', 'name_en' => 'Golden Power Chemicals', 'type' => 'local', 'contact_person' => 'Hassan Ibrahim', 'phone' => '01011223344', 'email' => 'hassan@gpc-eg.com', 'payment_terms' => 'Net 30']);
            $suppliers = Supplier::all()->keyBy('code');

            // Purchase Orders
            $poDefs = [
                ['supplier' => 'SUP-001', 'status' => 'confirmed', 'days' => 55, 'items' => [['sku' => 'VIR-PP-H030', 'qty' => 50, 'price' => 38000], ['sku' => 'VIR-PP-H530', 'qty' => 30, 'price' => 38500]]],
                ['supplier' => 'SUP-001', 'status' => 'received', 'days' => 45, 'items' => [['sku' => 'VIR-PE-HD56S', 'qty' => 40, 'price' => 41000]]],
                ['supplier' => 'SUP-002', 'status' => 'partial', 'days' => 30, 'items' => [['sku' => 'VIR-PE-HD6760', 'qty' => 25, 'price' => 42000], ['sku' => 'VIR-PET-REG', 'qty' => 15, 'price' => 36500]]],
                ['supplier' => 'SUP-003', 'status' => 'draft', 'days' => 15, 'items' => [['sku' => 'VIR-PE-LD200', 'qty' => 35, 'price' => 39500], ['sku' => 'VIR-PS-GPPS', 'qty' => 20, 'price' => 35500]]],
                ['supplier' => 'SUP-003', 'status' => 'confirmed', 'days' => 10, 'items' => [['sku' => 'VIR-PP-H030', 'qty' => 60, 'price' => 37800]]],
                ['supplier' => 'SUP-004', 'status' => 'received', 'days' => 20, 'items' => [['sku' => 'CHM-CACO3', 'qty' => 100, 'price' => 6200], ['sku' => 'CHM-STEARATE', 'qty' => 200, 'price' => 95]]],
            ];
            foreach ($poDefs as $poDef) {
                $orderDate = today()->subDays($poDef['days']);
                $subtotal = 0;
                $poItems = [];
                foreach ($poDef['items'] as $it) {
                    $prod = $products->firstWhere('sku', $it['sku']);
                    if (! $prod) {
                        continue;
                    }
                    $lineTotal = $it['qty'] * $it['price'];
                    $subtotal += $lineTotal;
                    $poItems[] = ['product' => $prod, 'qty' => $it['qty'], 'price' => $it['price'], 'line' => $lineTotal];
                }
                if (empty($poItems)) {
                    continue;
                }
                $po = PurchaseOrder::create([
                    'company_id' => $company->id, 'supplier_id' => $suppliers[$poDef['supplier']]->id,
                    'order_number' => $names->generate('purchase_order', $company->id),
                    'status' => $poDef['status'], 'order_date' => $orderDate,
                    'expected_delivery_date' => $orderDate->copy()->addDays(30),
                    'currency' => 'EGP', 'subtotal' => $subtotal,
                    'shipping_cost' => round($subtotal * 0.05, 2), 'total' => round($subtotal * 1.05, 2),
                ]);
                foreach ($poItems as $it) {
                    PurchaseOrderItem::create(['purchase_order_id' => $po->id, 'product_id' => $it['product']->id, 'quantity' => $it['qty'], 'unit_price' => $it['price'], 'line_total' => $it['line']]);
                }
            }

            // Purchase Requests
            $reqStatuses = ['pending', 'pending', 'pending', 'sales_approved', 'sales_approved', 'purchasing_approved', 'rejected_by_sales', 'purchasing_approved', 'pending', 'pending'];
            for ($i = 0; $i < 10; $i++) {
                $prod = $products->random();
                PurchaseRequest::create(['company_id' => $company->id, 'user_id' => $i % 2 === 0 ? $rep1->id : $rep2->id, 'product_id' => $prod->id, 'quantity' => rand(5, 40), 'offered_price' => (float) $prod->cost * (rand(90, 110) / 100), 'currency' => 'EGP', 'status' => $reqStatuses[$i]]);
            }

            // Invoices
            $invoices = [];
            for ($i = 0; $i < 40; $i++) {
                $rep = $i % 3 === 0 ? $rep2 : $rep1;
                $van = $rep->id === $rep1->id ? $van1 : $van2;
                $daysAgo = rand(0, 85);
                if ($daysAgo < 3) {
                    $daysAgo = rand(0, 3);
                }
                $issueDate = today()->subDays($daysAgo);

                $itemCount = rand(1, 3);
                $items = [];
                $subtotal = 0;
                for ($j = 0; $j < $itemCount; $j++) {
                    $prod = $products->random();
                    $qty = rand(1, 8);
                    $price = (float) $prod->price;
                    $lineTotal = $qty * $price;
                    $subtotal += $lineTotal;
                    $items[] = ['product' => $prod, 'qty' => $qty, 'price' => $price, 'line' => $lineTotal];
                }
                $vat = round($subtotal * 0.14, 2);
                $total = $subtotal + $vat;
                $invStatus = match (rand(1, 10)) {
                    1, 2 => 'cancelled',
                    3, 4, 5, 6 => 'paid',
                    default => 'submitted',
                };

                $inv = Invoice::create(['company_id' => $company->id, 'customer_id' => $customers->random()->id, 'user_id' => $rep->id, 'invoice_number' => $names->generate('sales_invoice', $company->id), 'status' => $invStatus, 'subtotal' => $subtotal, 'vat_amount' => $vat, 'total' => $total, 'paid_amount' => $invStatus === 'paid' ? $total : 0, 'remaining_amount' => $invStatus === 'paid' ? 0 : $total, 'posting_date' => $issueDate, 'issued_at' => $issueDate->setTime(9 + rand(0, 8), rand(0, 59)), 'cancelled_at' => $invStatus === 'cancelled' ? $issueDate->copy()->addDays(1) : null]);
                foreach ($items as $it) {
                    InvoiceItem::create(['invoice_id' => $inv->id, 'product_id' => $it['product']->id, 'quantity' => $it['qty'], 'unit_price' => $it['price'], 'line_total' => $it['line']]);
                }
                if ($invStatus !== 'cancelled' && $van) {
                    foreach ($items as $it) {
                        try {
                            $stockService->decrement($van->id, $it['product']->id, null, $it['qty'], StockReason::Sale, $inv);
                        } catch (\Throwable) {
                        }
                    }
                }
                $invoices[] = $inv;
            }

            // Payments
            $paymentMethods = ['cash', 'cheque', 'transfer'];
            foreach ($invoices as $inv) {
                if ($inv->status === InvoiceStatus::Cancelled || rand(1, 10) > 8) {
                    continue;
                }
                $amount = rand(1, 10) <= 6 ? $inv->total : round((float) $inv->total * (rand(3, 8) / 10), 2);
                $method = $paymentMethods[array_rand($paymentMethods)];
                Payment::create([
                    'company_id' => $company->id,
                    'customer_id' => $inv->customer_id,
                    'user_id' => $inv->user_id,
                    'invoice_id' => $inv->id,
                    'amount' => $amount,
                    'method' => $method,
                    'payment_number' => $names->generate('payment', $company->id, (int) $inv->issued_at->format('Y')),
                    'collected_at' => $inv->issued_at->copy()->addDays(rand(0, 15)),
                    'posting_date' => $inv->issued_at,
                ]);
                $inv->update(['paid_amount' => (float) $inv->paid_amount + $amount, 'remaining_amount' => (float) $inv->remaining_amount - $amount]);
                if ($inv->remaining_amount <= 0) {
                    $inv->update(['status' => 'paid']);
                } elseif ($inv->paid_amount > 0 && $inv->status !== InvoiceStatus::Paid) {
                    $inv->update(['status' => 'partially_paid']);
                }
            }

            // Returns
            $returnCount = 0;
            foreach (collect($invoices)->where('status', 'paid')->random(min(4, count($invoices))) as $inv) {
                $returnCount++;
                $item = $inv->items->first();
                if (! $item) {
                    continue;
                }
                $ret = ReturnRecord::create(['company_id' => $company->id, 'customer_id' => $inv->customer_id, 'user_id' => $inv->user_id, 'against_invoice_id' => $inv->id, 'return_number' => 'RET-'.now()->format('Ymd').'-'.$returnCount, 'total' => $item->line_total, 'reason' => 'منتج تالف / Damaged product', 'status' => 'submitted', 'returned_at' => $inv->issued_at->copy()->addDays(rand(2, 10)), 'posting_date' => $inv->issued_at->copy()->addDays(rand(2, 10))]);
                ReturnItem::create(['return_id' => $ret->id, 'product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'line_total' => $item->line_total]);
                $van = Warehouse::where('user_id', $inv->user_id)->where('type', 'van')->first();
                if ($van) {
                    $stockService->increment($van->id, $item->product_id, null, (float) $item->quantity, StockReason::Return, $ret);
                }
            }

            // Work sessions for visits
            $ws1 = WorkSession::firstOrCreate(['user_id' => $rep1->id, 'route_id' => $customers->first()->route_id], ['started_at' => now()->subHours(2), 'start_latitude' => 30.0444, 'start_longitude' => 31.2357]);
            $ws2 = WorkSession::firstOrCreate(['user_id' => $rep2->id, 'route_id' => $customers->skip(5)->first()->route_id], ['started_at' => now()->subHours(3), 'start_latitude' => 30.0131, 'start_longitude' => 31.2089]);

            // Visits
            foreach ($customers as $i => $cust) {
                if ($i >= 15) {
                    break;
                }
                $rep = $cust->route_id ? ($cust->route_id === $customers->first()->route_id ? $rep1 : $rep2) : $rep1;
                $ws = $rep->id === $rep1->id ? $ws1 : $ws2;
                $checkin = today()->subDays(rand(0, 7))->setTime(9 + rand(0, 5), rand(0, 59));
                Visit::firstOrCreate(['user_id' => $rep->id, 'customer_id' => $cust->id, 'checkin_at' => $checkin], ['route_id' => $cust->route_id, 'work_session_id' => $ws->id, 'purpose' => 'sale', 'status' => 'closed', 'checkin_latitude' => $cust->latitude, 'checkin_longitude' => $cust->longitude, 'checkout_at' => $checkin->copy()->addMinutes(rand(20, 90))]);
            }

            // Expenses
            $expenseCategories = ['fuel', 'food', 'maintenance', 'food', 'fuel', 'food', 'maintenance', 'fuel', 'other', 'fuel', 'food', 'food', 'fuel'];
            foreach ($expenseCategories as $cat) {
                $rep = rand(0, 1) ? $rep1 : $rep2;
                $amounts = ['fuel' => [200, 500], 'food' => [50, 150], 'maintenance' => [300, 1500], 'other' => [50, 300]];
                Expense::create(['company_id' => $company->id, 'user_id' => $rep->id, 'category' => $cat, 'amount' => rand($amounts[$cat][0], $amounts[$cat][1]), 'note' => $cat === 'fuel' ? 'وقود' : ($cat === 'food' ? 'غداء' : 'صيانة'), 'spent_at' => today()->subDays(rand(0, 25)), 'posting_date' => today()->subDays(rand(0, 25))]);
            }

            // Alarms
            $alarmDefs = [['type' => 'out_of_stock_request', 'title' => 'طلب مخزون - بولي كربونات', 'severity' => 'warning'], ['type' => 'out_of_stock_request', 'title' => 'طلب مخزون - TiO2', 'severity' => 'warning'], ['type' => 'customer_complaint', 'title' => 'شكوى عميل - تأخر تسليم', 'severity' => 'critical'], ['type' => 'customer_complaint', 'title' => 'شكوى - جودة r-PVC', 'severity' => 'warning'], ['type' => 'batch_expiring', 'title' => 'دفعة أوشكت على الانتهاء - r-PET', 'severity' => 'info'], ['type' => 'purchase_request', 'title' => 'طلب شراء - PP H030', 'severity' => 'info'], ['type' => 'purchase_request', 'title' => 'طلب شراء - PE-LD200', 'severity' => 'info'], ['type' => 'goods_in_transit_delayed', 'title' => 'تأخير شحنة - SABIC PP', 'severity' => 'warning']];
            foreach ($alarmDefs as $ad) {
                Alarm::create(['company_id' => $company->id, 'type' => $ad['type'], 'title' => $ad['title'], 'description' => $ad['title'], 'severity' => $ad['severity'], 'is_read' => rand(0, 1)]);
            }

            // Quotations
            $quoteStatuses = ['requested', 'requested', 'priced', 'priced', 'confirmed', 'requested', 'requested'];
            foreach ($quoteStatuses as $i => $status) {
                PriceQuotationRequest::create(['company_id' => $company->id, 'customer_id' => $customers->random()->id, 'user_id' => $i % 2 === 0 ? $rep1->id : $rep2->id, 'product_id' => $products->random()->id, 'quantity_requested' => rand(5, 50), 'status' => $status, 'requested_at' => now()->subDays(rand(0, 14))]);
            }

            $this->info('Invoices: '.count($invoices));
            $this->info('Payments: '.Payment::count());
            $this->info('Purchase Orders: '.PurchaseOrder::count());
            $this->info('Alarms: '.Alarm::count());
            $this->info('Visits: '.Visit::count());
            $this->info('Expenses: '.Expense::count());
        });

        fwrite(STDERR, "[seed-transactions] Complete.\n");
        $this->info('Transactional demo data seeded successfully.');

        return 0;
    }
}
