<?php

use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedgerReconciliationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->company = Company::factory()->create();
});

test('report returns empty when balanced', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'balance' => 0]);
    Invoice::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'user_id' => User::factory()->create(['company_id' => $this->company->id])->id,
        'status' => 'draft',
        'subtotal' => 0,
        'vat_amount' => 0,
        'remaining_amount' => 0,
        'total' => 0,
        'invoice_number' => 'INV-001',
        'issued_at' => now(),
    ]);

    $result = app(LedgerReconciliationService::class)->report($this->company->id);

    $this->assertEmpty($result['customers']);
    $this->assertEmpty($result['cash_boxes']);
    $this->assertEmpty($result['stocks']);
});

test('report detects customer balance drift', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'balance' => 500]);
    $user = User::factory()->create(['company_id' => $this->company->id]);
    Invoice::create([
        'company_id' => $this->company->id,
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'status' => 'submitted',
        'subtotal' => 300,
        'vat_amount' => 0,
        'remaining_amount' => 300,
        'total' => 300,
        'invoice_number' => 'INV-002',
        'issued_at' => now(),
    ]);

    $result = app(LedgerReconciliationService::class)->report($this->company->id);

    $this->assertCount(1, $result['customers']);
    $this->assertSame($customer->id, $result['customers'][0]['id']);
    $this->assertSame('500.00', $result['customers'][0]['stored']);
    $this->assertSame('300.00', $result['customers'][0]['ledger']);
});

test('report detects cash box drift', function () {
    $user = User::factory()->create(['company_id' => $this->company->id]);
    CashBox::create(['user_id' => $user->id, 'company_id' => $this->company->id, 'balance' => 1000]);
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    Payment::create([
        'company_id' => $this->company->id,
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'amount' => 500,
        'method' => 'cash',
        'collected_at' => now(),
    ]);

    $result = app(LedgerReconciliationService::class)->report($this->company->id);

    $this->assertCount(1, $result['cash_boxes']);
    $this->assertSame('1000.00', $result['cash_boxes'][0]['stored']);
    $this->assertSame('500.00', $result['cash_boxes'][0]['ledger']);
});

test('report detects stock drift', function () {
    $warehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'main']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);
    Stock::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 100,
    ]);
    StockMovement::create([
        'company_id' => $this->company->id,
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity_change' => 80,
        'reason' => 'initial',
        'user_id' => User::factory()->create(['company_id' => $this->company->id])->id,
    ]);

    $result = app(LedgerReconciliationService::class)->report($this->company->id);

    $this->assertCount(1, $result['stocks']);
    $this->assertSame('100.000', $result['stocks'][0]['stored']);
    $this->assertSame('80.000', $result['stocks'][0]['ledger']);
});
