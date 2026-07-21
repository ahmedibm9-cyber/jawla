<?php

namespace Tests\Feature;

use App\Livewire\App\CollectPayment;
use App\Livewire\App\LogComplaint;
use App\Livewire\App\LogExpense;
use App\Livewire\App\LogReturn;
use App\Livewire\App\SalesFlow;
use App\Livewire\App\VisitFlow;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkSession;
use App\Support\ActiveCompanyContext;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CG2 offline UX: when the device is offline the client enqueues the write to the
 * IndexedDB outbox (JS) and then calls the component's queueOffline() so the rep
 * sees a "queued to sync" confirmation instead of an error. These tests exercise
 * the server-side half of that contract — the queued state renders and the form
 * is cleared — proving the queued blades compile (icons resolve, no fatals).
 */
class RepFlowOfflineUxTest extends TestCase
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
        $this->product = Product::where('sku', 'PP-H030')->firstOrFail();
        $this->actingAs($this->rep);
        app(ActiveCompanyContext::class)->setFromUser($this->rep);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_sale_flow_queues_offline_and_clears_the_cart(): void
    {
        Livewire::test(SalesFlow::class)
            ->call('selectCustomer', $this->customer->id)
            ->call('addToCart', $this->product->id)
            ->assertCount('cart', 1)
            ->call('queueOffline')
            ->assertSet('step', 'queued')
            ->assertCount('cart', 0)
            ->assertSet('customerId', 0)
            ->assertOk()
            ->assertSee(app()->getLocale() === 'ar' ? 'بانتظار المزامنة' : 'Queued to sync');
    }

    public function test_payment_flow_queues_offline(): void
    {
        Livewire::test(CollectPayment::class)
            ->set('customer_id', $this->customer->id)
            ->set('amount', 50)
            ->call('queueOffline')
            ->assertSet('success', true)
            ->assertSet('customer_id', null)
            ->assertOk();
    }

    public function test_expense_flow_queues_offline(): void
    {
        Livewire::test(LogExpense::class)
            ->set('category', 'fuel')
            ->set('amount', 25)
            ->call('queueOffline')
            ->assertSet('success', true)
            ->assertSet('amount', null)
            ->assertOk();
    }

    public function test_return_flow_queues_offline(): void
    {
        Livewire::test(LogReturn::class)
            ->set('customer_id', $this->customer->id)
            ->set('items.0.product_id', $this->product->id)
            ->set('items.0.quantity', 2)
            ->call('queueOffline')
            ->assertSet('success', true)
            ->assertSet('customer_id', null)
            ->assertOk();
    }

    public function test_complaint_flow_queues_offline(): void
    {
        Livewire::test(LogComplaint::class)
            ->set('customer_id', $this->customer->id)
            ->set('complaint_type', 'quality_issue')
            ->set('description', 'Damaged goods on arrival')
            ->call('queueOffline')
            ->assertSet('customer_id', null)
            ->assertOk();
    }

    public function test_visit_report_queues_offline(): void
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
            'arrival_confirmed' => true,
        ]);

        Livewire::test(VisitFlow::class, ['visit' => $visit])
            ->assertSet('step', 'report')
            ->set('summary', 'Met the manager, restocked shelves.')
            ->call('queueOffline')
            ->assertSet('queuedOffline', true)
            ->assertSet('step', 'done')
            ->assertOk();

        // Offline path must NOT write the report — sync applies it later.
        $this->assertDatabaseMissing('visit_reports', ['visit_id' => $visit->id]);
    }

    public function test_sync_queue_page_renders_for_a_rep(): void
    {
        $this->get('/app/sync-queue')
            ->assertOk()
            ->assertSee('jawlaSyncQueue', false);
    }
}
