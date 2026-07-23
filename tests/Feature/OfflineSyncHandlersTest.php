<?php

namespace Tests\Feature;

use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Visit;
use App\Models\Warehouse;
use App\Models\WorkSession;
use App\Services\InvoiceService;
use App\Services\Sync\SyncHandlerRegistry;
use App\Services\Sync\SyncService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CG2 offline handlers: every rep write (sale/payment/return/expense/complaint/
 * visit_report) replays through its existing service exactly once, is company
 * scoped, and validates its payload. Driven through the real SyncService engine.
 */
class OfflineSyncHandlersTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $this->customer = Customer::where('status', 'approved')->firstOrFail();
        $this->product = Product::where('sku', 'VIR-PP-H030')->firstOrFail();
        $this->actingAs($this->rep);
        app(ActiveCompanyContext::class)->setFromUser($this->rep);

        // Ensure cash box has sufficient balance for expense tests
        CashBox::updateOrCreate(
            ['user_id' => $this->rep->id],
            ['company_id' => $this->rep->company_id, 'balance' => 10000]
        );

        // Ensure customer has sufficient balance for return tests
        $this->customer->update(['balance' => 50000]);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    /** Run one operation through the engine and return its result row. */
    private function process(string $key, string $type, array $payload): array
    {
        return app(SyncService::class)->process($this->rep, [
            ['key' => $key, 'type' => $type, 'payload' => $payload],
        ])[0];
    }

    private function vanStock(): float
    {
        $van = Warehouse::where('user_id', $this->rep->id)->where('type', 'van')->firstOrFail();

        return (float) Stock::where('warehouse_id', $van->id)
            ->where('product_id', $this->product->id)->value('quantity');
    }

    public function test_all_rep_write_types_are_registered(): void
    {
        $registry = app(SyncHandlerRegistry::class);
        foreach (['sale', 'payment', 'return', 'expense', 'complaint', 'visit_report'] as $type) {
            $this->assertTrue($registry->has($type), "missing handler: {$type}");
        }
    }

    public function test_sale_applies_once_and_decrements_stock(): void
    {
        $before = $this->vanStock();
        $op = ['customer_id' => $this->customer->id, 'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 1000],
        ]];

        $first = $this->process('sale-1', 'sale', $op);
        $this->assertSame('applied', $first['status']);
        $this->assertArrayHasKey('invoice_number', $first['result']);
        $this->assertSame($before - 5, $this->vanStock());

        // Replaying the same key must NOT create a second invoice or decrement again.
        $second = $this->process('sale-1', 'sale', $op);
        $this->assertSame('duplicate', $second['status']);
        $this->assertSame($before - 5, $this->vanStock());
        $this->assertSame($first['result']['invoice_number'], $second['result']['invoice_number']);
    }

    public function test_payment_applies_and_credits_cash_box(): void
    {
        $invoice = app(InvoiceService::class)->create([
            'company_id' => $this->rep->company_id,
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 1000]],
        ]);

        $cashBefore = (float) CashBox::where('user_id', $this->rep->id)->value('balance');

        $r = $this->process('pay-1', 'payment', [
            'customer_id' => $this->customer->id,
            'amount' => 500,
            'method' => 'cash',
            'invoice_id' => $invoice->id,
        ]);

        $this->assertSame('applied', $r['status']);
        $this->assertDatabaseHas('payments', ['id' => $r['result']['payment_id'], 'amount' => 500]);
        $this->assertSame($cashBefore + 500, (float) CashBox::where('user_id', $this->rep->id)->value('balance'));
    }

    public function test_return_applies(): void
    {
        $r = $this->process('ret-1', 'return', [
            'customer_id' => $this->customer->id,
            'reason' => 'damaged',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000]],
        ]);

        $this->assertSame('applied', $r['status']);
        $this->assertDatabaseHas('returns', ['id' => $r['result']['return_id']]);
    }

    public function test_expense_applies(): void
    {
        $r = $this->process('exp-1', 'expense', [
            'category' => 'fuel',
            'amount' => 50,
            'note' => 'diesel',
        ]);

        $this->assertSame('applied', $r['status']);
        $this->assertDatabaseHas('expenses', ['id' => $r['result']['expense_id'], 'amount' => 50]);
    }

    public function test_complaint_applies(): void
    {
        $r = $this->process('cmp-1', 'complaint', [
            'customer_id' => $this->customer->id,
            'type' => 'quality_issue',
            'description' => 'product arrived damaged',
        ]);

        $this->assertSame('applied', $r['status']);
        $this->assertDatabaseHas('complaints', ['id' => $r['result']['complaint_id']]);
    }

    public function test_visit_report_applies_and_closes_visit(): void
    {
        $workSession = WorkSession::factory()->create([
            'company_id' => $this->rep->company_id,
            'user_id' => $this->rep->id,
            'route_id' => $this->customer->route_id,
        ]);
        $visit = Visit::create([
            'user_id' => $this->rep->id,
            'customer_id' => $this->customer->id,
            'route_id' => $this->customer->route_id,
            'work_session_id' => $workSession->id,
            'status' => 'open',
            'purpose' => 'sale',
        ]);

        $r = $this->process('vr-1', 'visit_report', [
            'visit_id' => $visit->id,
            'summary' => 'Met the manager, restocked shelves.',
        ]);

        $this->assertSame('applied', $r['status']);
        $this->assertDatabaseHas('visit_reports', ['id' => $r['result']['visit_report_id'], 'visit_id' => $visit->id]);
        $this->assertSame('closed', $visit->fresh()->status->value);
    }

    public function test_cross_company_customer_is_rejected(): void
    {
        $other = Company::factory()->create();
        $theirCustomer = Customer::factory()->create(['company_id' => $other->id]);

        $r = $this->process('bad-1', 'sale', [
            'customer_id' => $theirCustomer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 1000]],
        ]);

        $this->assertSame('failed', $r['status']);
        $this->assertDatabaseMissing('sync_receipts', ['idempotency_key' => 'bad-1']);
    }

    public function test_invalid_payload_fails_without_receipt(): void
    {
        $r = $this->process('inv-1', 'sale', ['customer_id' => $this->customer->id, 'items' => []]);

        $this->assertSame('failed', $r['status']);
        $this->assertDatabaseMissing('sync_receipts', ['idempotency_key' => 'inv-1']);
    }

    public function test_endpoint_applies_a_full_batch(): void
    {
        $this->postJson('/app/sync', [
            'operations' => [
                ['key' => 'b-cmp', 'type' => 'complaint', 'payload' => [
                    'customer_id' => $this->customer->id,
                    'type' => 'other',
                    'description' => 'batch complaint',
                ]],
                ['key' => 'b-exp', 'type' => 'expense', 'payload' => [
                    'category' => 'other', 'amount' => 10,
                ]],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied')
            ->assertJsonPath('results.1.status', 'applied');
    }
}
