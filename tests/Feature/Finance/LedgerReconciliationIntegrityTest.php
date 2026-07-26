<?php

namespace Tests\Feature\Finance;

use App\Enums\StockReason;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Services\InvoiceService;
use App\Services\LedgerReconciliationService;
use App\Services\PaymentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerReconciliationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_detects_customer_cash_and_stock_summary_drift_without_correcting_it(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $customer = Customer::factory()->create(['company_id' => $company->id, 'balance' => 0]);
        $product = Product::factory()->create(['company_id' => $company->id, 'price' => 100]);
        $van = Warehouse::factory()->create([
            'company_id' => $company->id,
            'type' => 'van',
            'user_id' => $rep->id,
        ]);
        app(StockService::class)->increment(
            $van->id, $product->id, null, 10, StockReason::Initial, $product, $rep->id,
        );
        $this->actingAs($rep);
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);
        app(PaymentService::class)->collect(
            $company->id,
            $rep->id,
            $customer->id,
            50,
            'cash',
            $invoice->id,
            intentId: 'reconciliation-payment',
        );

        $service = app(LedgerReconciliationService::class);
        $this->assertSame(['customers' => [], 'cash_boxes' => [], 'stocks' => []], $service->report($company->id));

        $tamperedCustomerBalance = bcadd((string) $customer->fresh()->balance, '1.00', 2);
        Customer::whereKey($customer->id)->update(['balance' => $tamperedCustomerBalance]);
        CashBox::where('user_id', $rep->id)->update(['balance' => '52.00']);
        Stock::where('warehouse_id', $van->id)->where('product_id', $product->id)
            ->update(['quantity' => '10.000']);

        $report = $service->report($company->id);
        $this->assertSame('1.00', $report['customers'][0]['difference']);
        $this->assertSame('2.00', $report['cash_boxes'][0]['difference']);
        $this->assertSame('1.000', $report['stocks'][0]['difference']);
        $this->assertSame($tamperedCustomerBalance, $customer->fresh()->balance);
        $this->assertSame('52.00', CashBox::where('user_id', $rep->id)->value('balance'));
    }
}
