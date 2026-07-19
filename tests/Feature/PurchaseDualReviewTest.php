<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseRequestResource\Pages\ListPurchaseRequests;
use App\Livewire\App\Orders;
use App\Livewire\App\SubmitPurchaseOffer;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseRequestService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseDualReviewTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $otherRep;

    private User $salesManager;

    private User $purchasing;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->rep = $this->makeUser('rep');
        $this->otherRep = $this->makeUser('rep');
        $this->salesManager = $this->makeUser('sales_manager');
        $this->purchasing = $this->makeUser('purchasing');
        $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
    }

    private function makeUser(string $role, ?int $companyId = null): User
    {
        $user = User::factory()->create(['company_id' => $companyId ?? $this->company->id]);
        $user->assignRole($role);

        return $user;
    }

    private function makeOffer(array $overrides = []): PurchaseRequest
    {
        return PurchaseRequest::create(array_merge([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'supplier_id' => $this->supplier->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'offered_price' => 250.50,
            'currency' => 'EGP',
            'payment_terms' => 'Net 30',
            'status' => 'pending',
        ], $overrides));
    }

    private function service(): PurchaseRequestService
    {
        return app(PurchaseRequestService::class);
    }

    public function test_purchasing_cannot_decide_before_sales(): void
    {
        $offer = $this->makeOffer();

        $this->expectException(\RuntimeException::class);

        try {
            $this->service()->purchasingApprove($offer, $this->purchasing->id);
        } finally {
            $this->assertSame('pending', $offer->fresh()->status);
            $this->assertDatabaseCount('purchase_orders', 0);
        }
    }

    public function test_sales_approve_records_reviewer_and_notifies_rep(): void
    {
        $offer = $this->makeOffer();

        $this->service()->salesApprove($offer, $this->salesManager->id, 'good price');

        $offer->refresh();
        $this->assertSame('sales_approved', $offer->status);
        $this->assertSame($this->salesManager->id, $offer->sales_reviewed_by);
        $this->assertNotNull($offer->sales_reviewed_at);
        $this->assertSame('good price', $offer->sales_review_notes);

        $this->assertSame(1, $this->rep->notifications()->count());
        $this->assertSame(0, $this->otherRep->notifications()->count());
        $this->assertSame('info', $this->rep->notifications()->first()->data['severity']);
    }

    public function test_sales_reject_keeps_offer_renegotiable_and_notifies_with_reason(): void
    {
        $offer = $this->makeOffer();

        $this->service()->salesReject($offer, $this->salesManager->id, 'Price too high');

        $offer->refresh();
        $this->assertSame('rejected_by_sales', $offer->status);
        $this->assertSame('Price too high', $offer->sales_review_notes);

        $data = $this->rep->notifications()->first()->data;
        $this->assertSame('critical', $data['severity']);
        $this->assertStringContainsString('Price too high', $data['body_en']);
        $this->assertStringContainsString('Price too high', $data['body_ar']);
    }

    public function test_purchasing_approve_generates_po_with_sequential_numbers(): void
    {
        $offer = $this->makeOffer();
        $this->service()->salesApprove($offer, $this->salesManager->id);
        $order = $this->service()->purchasingApprove($offer, $this->purchasing->id);

        $offer->refresh();
        $this->assertSame('purchasing_approved', $offer->status);
        $this->assertSame($order->id, $offer->purchase_order_id);
        $this->assertSame('draft', $order->status);
        $this->assertSame($this->supplier->id, $order->supplier_id);
        $this->assertSame($this->company->id, $order->company_id);
        $this->assertSame(2505.00, (float) $order->total);
        $this->assertStringEndsWith('00001', $order->order_number);

        $item = $order->items()->first();
        $this->assertSame($this->product->id, $item->product_id);
        $this->assertSame(10.0, (float) $item->quantity);
        $this->assertSame(250.50, (float) $item->unit_price);
        $this->assertSame(2505.00, (float) $item->line_total);

        $second = $this->makeOffer();
        $this->service()->salesApprove($second, $this->salesManager->id);
        $secondOrder = $this->service()->purchasingApprove($second, $this->purchasing->id);
        $this->assertStringEndsWith('00002', $secondOrder->order_number);
        $this->assertNotSame($order->order_number, $secondOrder->order_number);

        // The purchasing-approved notification carries the PO number (same-second
        // rows make ordering non-deterministic, so match on content, not position).
        $this->assertTrue(
            $this->rep->notifications()->get()->contains(
                fn ($n) => str_contains($n->data['body_en'], $secondOrder->order_number)
            )
        );
    }

    public function test_purchasing_approve_requires_supplier(): void
    {
        $offer = $this->makeOffer(['supplier_id' => null]);
        $this->service()->salesApprove($offer, $this->salesManager->id);

        try {
            $this->service()->purchasingApprove($offer, $this->purchasing->id);
            $this->fail('Expected RuntimeException for missing supplier');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('supplier', strtolower($e->getMessage()));
        }

        $this->assertSame('sales_approved', $offer->fresh()->status);
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_rejected_offer_can_be_edited_and_resubmitted(): void
    {
        $offer = $this->makeOffer();
        $this->service()->salesReject($offer, $this->salesManager->id, 'Too expensive');

        $this->service()->resubmit($offer, $this->rep->id, ['offered_price' => 199.99]);

        $offer->refresh();
        $this->assertSame('pending', $offer->status);
        $this->assertSame(199.99, (float) $offer->offered_price);
        $this->assertNull($offer->sales_reviewed_by);
        $this->assertNull($offer->sales_reviewed_at);
        $this->assertNull($offer->sales_review_notes);
        $this->assertNull($offer->purchasing_reviewed_by);
    }

    public function test_resubmit_limited_to_rejected_offers_and_owner(): void
    {
        $pending = $this->makeOffer();

        try {
            $this->service()->resubmit($pending, $this->rep->id);
            $this->fail('Expected RuntimeException for non-rejected offer');
        } catch (\RuntimeException) {
            $this->assertSame('pending', $pending->fresh()->status);
        }

        $rejected = $this->makeOffer();
        $this->service()->salesReject($rejected, $this->salesManager->id);

        try {
            $this->service()->resubmit($rejected, $this->otherRep->id);
            $this->fail('Expected RuntimeException for non-owner resubmit');
        } catch (\RuntimeException) {
            $this->assertSame('rejected_by_sales', $rejected->fresh()->status);
        }
    }

    public function test_expired_offer_cannot_be_approved_but_can_be_rejected(): void
    {
        $offer = $this->makeOffer(['expires_at' => now()->subDay()->toDateString()]);

        try {
            $this->service()->salesApprove($offer, $this->salesManager->id);
            $this->fail('Expected RuntimeException for expired offer');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('expired', strtolower($e->getMessage()));
        }

        $this->service()->salesReject($offer, $this->salesManager->id, 'expired');
        $this->assertSame('rejected_by_sales', $offer->fresh()->status);
    }

    public function test_offer_expiring_today_is_still_approvable(): void
    {
        $offer = $this->makeOffer(['expires_at' => now()->toDateString()]);

        $this->service()->salesApprove($offer, $this->salesManager->id);

        $this->assertSame('sales_approved', $offer->fresh()->status);
    }

    public function test_double_decision_is_rejected_and_creates_no_duplicate_po(): void
    {
        $offer = $this->makeOffer();
        $this->service()->salesApprove($offer, $this->salesManager->id);

        try {
            $this->service()->salesApprove($offer, $this->salesManager->id);
            $this->fail('Expected RuntimeException on duplicate sales approval');
        } catch (\RuntimeException) {
        }

        $this->service()->purchasingApprove($offer, $this->purchasing->id);

        try {
            $this->service()->purchasingApprove($offer, $this->purchasing->id);
            $this->fail('Expected RuntimeException on duplicate purchasing approval');
        } catch (\RuntimeException) {
        }

        $this->assertDatabaseCount('purchase_orders', 1);
    }

    public function test_filament_actions_run_the_full_dual_review_with_notes(): void
    {
        $offer = $this->makeOffer();

        Livewire::actingAs($this->salesManager)
            ->test(ListPurchaseRequests::class)
            ->callTableAction('sales_approve', $offer, ['notes' => 'looks good']);

        $offer->refresh();
        $this->assertSame('sales_approved', $offer->status);
        $this->assertSame('looks good', $offer->sales_review_notes);

        Livewire::actingAs($this->purchasing)
            ->test(ListPurchaseRequests::class)
            ->callTableAction('purchasing_approve', $offer, ['notes' => 'ordering']);

        $offer->refresh();
        $this->assertSame('purchasing_approved', $offer->status);
        $this->assertNotNull($offer->purchase_order_id);
        $this->assertSame('ordering', $offer->purchasing_review_notes);
        $this->assertSame(2, $this->rep->notifications()->count());
    }

    public function test_cross_company_manager_cannot_see_or_decide_foreign_offers(): void
    {
        $offer = $this->makeOffer();

        $foreignCompany = Company::factory()->create();
        $foreignManager = $this->makeUser('sales_manager', $foreignCompany->id);

        // Mirror the panel middleware: the active company scope is bound to the
        // signed-in user's company, so a foreign manager's tables exclude it.
        app(ActiveCompanyContext::class)->setFromUser($foreignManager);

        Livewire::actingAs($foreignManager)
            ->test(ListPurchaseRequests::class)
            ->assertCanNotSeeTableRecords([$offer]);

        app(ActiveCompanyContext::class)->disable();
        $this->assertSame('pending', $offer->fresh()->status);
    }

    public function test_rep_orders_page_lists_offers_with_resubmit_entry(): void
    {
        $rejected = $this->makeOffer();
        $this->service()->salesReject($rejected, $this->salesManager->id, 'renegotiate');

        Livewire::actingAs($this->rep)
            ->test(Orders::class)
            ->call('setType', 'offers')
            ->assertSee($this->product->name_ar)
            ->assertSee(__('app.status_rejected_by_sales'))
            ->assertSee('renegotiate')
            ->assertSee("/app/purchase-offer?resubmit={$rejected->id}", false);
    }

    public function test_rep_can_submit_offer_with_expiry(): void
    {
        $expiry = now()->addWeek()->toDateString();

        Livewire::actingAs($this->rep)
            ->test(SubmitPurchaseOffer::class)
            ->set('product_id', $this->product->id)
            ->set('supplier_id', $this->supplier->id)
            ->set('quantity', 5)
            ->set('offered_price', 100)
            ->set('expires_at', $expiry)
            ->call('submit');

        $offer = PurchaseRequest::where('user_id', $this->rep->id)->firstOrFail();
        $this->assertSame($expiry, $offer->expires_at->toDateString());
        $this->assertSame('pending', $offer->status);
    }

    public function test_rep_resubmits_rejected_offer_through_the_page(): void
    {
        $offer = $this->makeOffer();
        $this->service()->salesReject($offer, $this->salesManager->id, 'Lower the price');

        $this->actingAs($this->rep);

        Livewire::withQueryParams(['resubmit' => $offer->id])
            ->test(SubmitPurchaseOffer::class)
            ->assertSet('offered_price', 250.50)
            ->assertSee('Lower the price')
            ->set('offered_price', 220)
            ->call('submit')
            ->assertHasNoErrors();

        $offer->refresh();
        $this->assertSame('pending', $offer->status);
        $this->assertSame(220.0, (float) $offer->offered_price);
        $this->assertNull($offer->sales_review_notes);
    }

    public function test_other_rep_cannot_hijack_resubmission_via_query_param(): void
    {
        $offer = $this->makeOffer();
        $this->service()->salesReject($offer, $this->salesManager->id);

        $this->actingAs($this->otherRep);

        Livewire::withQueryParams(['resubmit' => $offer->id])
            ->test(SubmitPurchaseOffer::class)
            ->assertSet('resubmit', null)
            ->assertSet('offered_price', null);
    }
}
