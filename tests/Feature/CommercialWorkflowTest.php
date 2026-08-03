<?php

namespace Tests\Feature;

use App\Data\ReturnStockDestination;
use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\StalePriceException;
use App\Models\CashBox;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Photo;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CollectionSubmissionService;
use App\Services\ReturnRequestService;
use App\Services\SalesOrderService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $manager;

    private User $finance;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = User::factory()->for($this->company)->create()->assignRole('rep');
        $this->manager = User::factory()->for($this->company)->create()->assignRole('sales_manager');
        $this->finance = User::factory()->for($this->company)->create()->assignRole('accounts');
        $this->customer = Customer::factory()->for($this->company)->create(['route_id' => null, 'balance' => 100]);
        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_sales_order_is_independent_from_invoice_and_stock_until_approved(): void
    {
        $category = ProductCategory::factory()->for($this->company)->create();
        $product = Product::factory()->for($this->company)->for($category, 'category')->create(['price' => 25]);

        $order = app(SalesOrderService::class)->createAndSubmit($this->rep, $this->customer->id, [[
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 25,
        ]]);

        self::assertSame('submitted', $order->status);
        self::assertSame('75.00', $order->total);
        self::assertNull($order->invoice_id);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        $approval = $order->latestApproval()->firstOrFail();
        app(SalesOrderService::class)->approve($approval, $this->manager);

        self::assertSame('approved', $order->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_sales_order_rejects_tampered_and_cross_company_prices(): void
    {
        $category = ProductCategory::factory()->for($this->company)->create();
        $product = Product::factory()->for($this->company)->for($category, 'category')->create(['price' => 25]);

        try {
            app(SalesOrderService::class)->createAndSubmit($this->rep, $this->customer->id, [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 0,
            ]]);
            $this->fail('A client-controlled price must not be accepted.');
        } catch (StalePriceException) {
            $this->assertDatabaseCount('sales_orders', 0);
        }

        $otherCompany = Company::factory()->create();
        $foreignProduct = app(ActiveCompanyContext::class)->runWithCompany(
            $otherCompany->id,
            function () use ($otherCompany): Product {
                $foreignCategory = ProductCategory::factory()->for($otherCompany)->create();

                return Product::factory()->for($otherCompany)->for($foreignCategory, 'category')->create();
            },
        );

        $this->expectException(ModelNotFoundException::class);
        app(SalesOrderService::class)->createAndSubmit($this->rep, $this->customer->id, [[
            'product_id' => $foreignProduct->id,
            'quantity' => 1,
            'unit_price' => $foreignProduct->price,
        ]]);
    }

    public function test_collection_posts_no_money_until_manager_approval(): void
    {
        $evidence = $this->evidencePhoto();
        $submission = app(CollectionSubmissionService::class)->submit(
            $this->rep,
            $this->customer->id,
            40,
            'cash',
            ['evidence_photo_ids' => [$evidence->id]],
        );

        self::assertSame('pending_review', $submission->status);
        $this->assertDatabaseCount('payments', 0);
        self::assertSame('100.00', $this->customer->fresh()->balance);

        app(CollectionSubmissionService::class)->approve(
            $submission->latestApproval()->firstOrFail(),
            $this->manager,
        );

        self::assertSame('supervisor_reviewed', $submission->fresh()->status);
        $this->assertDatabaseCount('payments', 0);

        app(CollectionSubmissionService::class)->approve(
            $submission->latestApproval()->firstOrFail(),
            $this->finance,
        );
        self::assertSame('finance_reviewed', $submission->fresh()->status);
        $this->assertDatabaseCount('payments', 0);

        app(CollectionSubmissionService::class)->reconcile($submission, $this->finance);

        self::assertSame('reconciled', $submission->fresh()->status);
        self::assertSame('60.00', $this->customer->fresh()->balance);
        self::assertSame('40.00', CashBox::query()->where('user_id', $this->rep->id)->value('balance'));
        self::assertSame($submission->id, (int) str_replace('collection-', '', Payment::firstOrFail()->intent_id));
    }

    public function test_collection_requires_owned_unattached_evidence(): void
    {
        try {
            app(CollectionSubmissionService::class)->submit($this->rep, $this->customer->id, 10, 'cash');
            $this->fail('Collection submission must require evidence.');
        } catch (\DomainException) {
            $this->assertDatabaseCount('collection_submissions', 0);
        }

        $otherCompany = Company::factory()->create();
        $foreignRep = User::factory()->for($otherCompany)->create();
        $foreignPhoto = app(ActiveCompanyContext::class)->runWithCompany(
            $otherCompany->id,
            fn () => Photo::factory()->create([
                'company_id' => $otherCompany->id,
                'user_id' => $foreignRep->id,
                'photable_type' => null,
                'photable_id' => null,
            ]),
        );

        $this->expectException(\DomainException::class);
        app(CollectionSubmissionService::class)->submit(
            $this->rep,
            $this->customer->id,
            10,
            'cash',
            ['evidence_photo_ids' => [$foreignPhoto->id]],
        );
    }

    public function test_pending_return_requests_reserve_invoice_quantity(): void
    {
        $category = ProductCategory::factory()->for($this->company)->create();
        $product = Product::factory()->for($this->company)->for($category, 'category')->create();
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->rep->id,
            'status' => InvoiceStatus::Paid,
        ]);
        $line = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'line_total' => 500,
            'tax_amount' => 70,
        ]);

        app(ReturnRequestService::class)->submit(
            $this->rep,
            $this->customer->id,
            $invoice->id,
            [['invoice_item_id' => $line->id, 'quantity' => 4, 'condition' => 'sellable']],
            'First request',
        );

        $this->expectException(\DomainException::class);
        app(ReturnRequestService::class)->submit(
            $this->rep,
            $this->customer->id,
            $invoice->id,
            [['invoice_item_id' => $line->id, 'quantity' => 2, 'condition' => 'sellable']],
            'Over-reserved request',
        );
    }

    public function test_return_changes_stock_and_financials_only_on_warehouse_receipt(): void
    {
        $warehouseKeeper = User::factory()->for($this->company)->create()->assignRole('warehouse_keeper');
        Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'van', 'user_id' => $this->rep->id]);
        $main = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'main']);
        $quarantine = Warehouse::factory()->create(['company_id' => $this->company->id, 'type' => 'quarantine']);
        $category = ProductCategory::factory()->for($this->company)->create();
        $product = Product::factory()->for($this->company)->for($category, 'category')->create();
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->rep->id,
            'status' => InvoiceStatus::Paid,
            'subtotal' => 500,
            'vat_amount' => 70,
            'total' => 570,
            'paid_amount' => 570,
            'remaining_amount' => 0,
        ]);
        $line = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 100,
            'line_total' => 500,
            'tax_amount' => 70,
        ]);

        $request = app(ReturnRequestService::class)->submit(
            $this->rep,
            $this->customer->id,
            $invoice->id,
            [['invoice_item_id' => $line->id, 'quantity' => 2, 'condition' => 'damaged']],
            'Damaged in transit',
        );

        self::assertSame('pending_approval', $request->status);
        self::assertSame('228.00', $request->total);
        $this->assertDatabaseCount('returns', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('customer_credits', 0);

        app(ReturnRequestService::class)->approve($request->latestApproval()->firstOrFail(), $this->manager);
        $this->assertDatabaseCount('returns', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        app(ReturnRequestService::class)->receive(
            $request,
            $warehouseKeeper,
            new ReturnStockDestination($main->id, $quarantine->id, $warehouseKeeper->id),
            'Counted and inspected',
        );

        self::assertSame('received', $request->fresh()->status);
        $this->assertDatabaseCount('returns', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        self::assertSame('2.000', $product->stocks()->whereHas('warehouse', fn ($query) => $query->where('type', 'quarantine'))->value('quantity'));
        self::assertSame('228.00', CustomerCredit::query()->value('amount'));
    }

    public function test_rejected_collection_never_posts_a_payment(): void
    {
        $submission = app(CollectionSubmissionService::class)->submit(
            $this->rep,
            $this->customer->id,
            10,
            'cash',
            ['evidence_photo_ids' => [$this->evidencePhoto()->id]],
        );

        app(CollectionSubmissionService::class)->reject(
            $submission->latestApproval()->firstOrFail(),
            $this->manager,
            'Receipt image is unreadable.',
        );

        $submission->refresh();
        self::assertSame('rejected', $submission->status);
        self::assertSame('Receipt image is unreadable.', $submission->review_reason);
        $this->assertDatabaseCount('payments', 0);
    }

    private function evidencePhoto(): Photo
    {
        return Photo::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'photable_type' => null,
            'photable_id' => null,
        ]);
    }
}
