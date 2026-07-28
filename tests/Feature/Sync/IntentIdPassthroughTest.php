<?php

namespace Tests\Feature\Sync;

use App\Models\CashBox;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Sync\SyncService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-004: The outbox-generated idempotency key must flow through to domain
 * services as intent_id, creating a single traceable ID from client enqueue
 * to server domain record.
 */
class IntentIdPassthroughTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $this->customer = Customer::where('status', 'approved')->firstOrFail();
        $this->actingAs($this->rep);
        app(ActiveCompanyContext::class)->setFromUser($this->rep);

        CashBox::updateOrCreate(
            ['user_id' => $this->rep->id],
            ['company_id' => $this->rep->company_id, 'balance' => 10000]
        );
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function process(string $key, string $type, array $payload): array
    {
        return app(SyncService::class)->process($this->rep, [
            ['key' => $key, 'type' => $type, 'payload' => $payload],
        ])[0];
    }

    public function test_payment_sync_stores_idempotency_key_as_intent_id(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->rep->company_id,
            'customer_id' => $this->customer->id,
            'status' => 'issued',
        ]);

        $outboxKey = 'offline-pay-'.uniqid();

        $r = $this->process($outboxKey, 'payment', [
            'customer_id' => $this->customer->id,
            'amount' => 100,
            'method' => 'cash',
            'invoice_id' => $invoice->id,
        ]);

        $this->assertSame('applied', $r['status']);

        // The payment's intent_id must match the outbox key
        $this->assertDatabaseHas('payments', [
            'id' => $r['result']['payment_id'],
            'intent_id' => $outboxKey,
        ]);
    }

    public function test_different_keys_produce_different_payments(): void
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->rep->company_id,
            'customer_id' => $this->customer->id,
            'status' => 'issued',
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'amount' => 200,
            'method' => 'cash',
            'invoice_id' => $invoice->id,
        ];

        $first = $this->process('offline-pay-a', 'payment', $payload);
        $this->assertSame('applied', $first['status']);

        // Different key → different sync receipt → different domain intent_id
        // guard → new payment created
        $second = $this->process('offline-pay-b', 'payment', $payload);
        $this->assertSame('applied', $second['status']);
        $this->assertNotSame($first['result']['payment_id'], $second['result']['payment_id']);
    }

    public function test_sale_handler_accepts_idempotency_key_without_error(): void
    {
        $product = Product::where('sku', 'VIR-PP-H030')->firstOrFail();
        $van = Warehouse::where('user_id', $this->rep->id)->where('type', 'van')->firstOrFail();

        $outboxKey = 'offline-sale-'.uniqid();
        $r = $this->process($outboxKey, 'sale', [
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $product->price]],
        ]);

        $this->assertSame('applied', $r['status']);
    }

    public function test_expense_handler_accepts_idempotency_key_without_error(): void
    {
        $outboxKey = 'offline-expense-'.uniqid();
        $r = $this->process($outboxKey, 'expense', [
            'category' => 'fuel',
            'amount' => 50,
            'note' => 'test',
        ]);

        $this->assertSame('applied', $r['status']);
    }

    public function test_payment_failure_does_not_leak_intent_id(): void
    {
        // Payment for a nonexistent customer must fail at the service layer.
        $outboxKey = 'offline-pay-fail-'.uniqid();
        $r = $this->process($outboxKey, 'payment', [
            'customer_id' => 999999,
            'amount' => 100,
            'method' => 'cash',
        ]);

        $this->assertSame('failed', $r['status']);
        $this->assertDatabaseMissing('payments', ['intent_id' => $outboxKey]);
    }
}
