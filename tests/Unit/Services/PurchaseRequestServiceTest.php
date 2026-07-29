<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contracts\DocumentNumberService;
use App\Services\PurchaseRequestService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->app->bind(DocumentNumberService::class, fn () => new class implements DocumentNumberService
        {
            public function generate(string $docType, int $companyId, ?int $year = null): string
            {
                return 'PO-00001';
            }
        });
    }

    private function makeRequest(Company $company, User $rep): PurchaseRequest
    {
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create(['company_id' => $company->id]);

        return PurchaseRequest::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'quantity' => '10.000',
            'offered_price' => '25.00',
            'currency' => 'SAR',
            'payment_terms' => 'net30',
            'status' => 'pending',
        ]);
    }

    public function test_sales_approve_changes_status(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $manager = User::factory()->create(['company_id' => $company->id]);

        $request = $this->makeRequest($company, $rep);

        $result = app(PurchaseRequestService::class)->salesApprove($request, $manager->id, 'Looks good');

        $this->assertSame('sales_approved', $result->status);
        $this->assertSame($manager->id, $result->sales_reviewed_by);
        $this->assertNotNull($result->sales_reviewed_at);
        $this->assertSame('Looks good', $result->sales_review_notes);
    }

    public function test_sales_reject_changes_status(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $manager = User::factory()->create(['company_id' => $company->id]);

        $request = $this->makeRequest($company, $rep);

        $result = app(PurchaseRequestService::class)->salesReject($request, $manager->id, 'Too expensive');

        $this->assertSame('rejected_by_sales', $result->status);
        $this->assertSame($manager->id, $result->sales_reviewed_by);
        $this->assertSame('Too expensive', $result->sales_review_notes);
    }

    public function test_purchasing_approve_creates_purchase_order(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $purchaser = User::factory()->create(['company_id' => $company->id]);

        $request = $this->makeRequest($company, $rep);
        app(PurchaseRequestService::class)->salesApprove($request, $rep->id);

        $order = app(PurchaseRequestService::class)->purchasingApprove($request, $purchaser->id);

        $this->assertInstanceOf(PurchaseOrder::class, $order);
        $this->assertSame($company->id, $order->company_id);
        $this->assertSame($request->supplier_id, $order->supplier_id);
        $this->assertSame('PO-00001', $order->order_number);
        $this->assertSame('draft', $order->status);

        $request->refresh();
        $this->assertSame('purchasing_approved', $request->status);
        $this->assertSame($order->id, $request->purchase_order_id);
    }

    public function test_purchasing_reject_changes_status(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $purchaser = User::factory()->create(['company_id' => $company->id]);

        $request = $this->makeRequest($company, $rep);
        app(PurchaseRequestService::class)->salesApprove($request, $rep->id);

        $result = app(PurchaseRequestService::class)->purchasingReject($request, $purchaser->id, 'Not available');

        $this->assertSame('rejected_by_purchasing', $result->status);
        $this->assertSame($purchaser->id, $result->purchasing_reviewed_by);
    }

    public function test_resubmit_resets_to_pending(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        $request = $this->makeRequest($company, $rep);
        app(PurchaseRequestService::class)->salesReject($request, $rep->id);

        $result = app(PurchaseRequestService::class)->resubmit($request, $rep->id, [
            'offered_price' => '20.00',
        ]);

        $this->assertSame('pending', $result->status);
        $this->assertNull($result->sales_reviewed_by);
        $this->assertNull($result->purchasing_reviewed_by);
        $this->assertSame('20.00', $result->offered_price);
    }

    public function test_resubmit_only_from_rejected_status(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);

        $request = $this->makeRequest($company, $rep);

        $this->expectException(\RuntimeException::class);
        app(PurchaseRequestService::class)->resubmit($request, $rep->id);
    }
}
