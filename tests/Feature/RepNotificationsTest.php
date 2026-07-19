<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\ComplaintService;
use App\Services\Contracts\OutOfStockService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RepNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $otherRep;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->rep = $this->makeUser('rep');
        $this->otherRep = $this->makeUser('rep');
        $this->manager = $this->makeUser('sales_manager');
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole($role);

        return $user;
    }

    private function makeComplaint(): Complaint
    {
        return app(ComplaintService::class)->log(
            $this->company->id,
            $this->rep->id,
            Customer::factory()->create(['company_id' => $this->company->id])->id,
            'quality_issue',
            'Product arrived damaged',
        );
    }

    public function test_complaint_resolution_notifies_submitting_rep_exactly_once(): void
    {
        $complaint = $this->makeComplaint();

        app(ComplaintService::class)->resolve($complaint, $this->manager->id, 'Replacement sent');

        $this->assertSame(1, $this->rep->notifications()->count());
        $this->assertSame(0, $this->otherRep->notifications()->count());

        $data = $this->rep->notifications()->first()->data;
        $this->assertSame('info', $data['severity']);
        $this->assertStringContainsString('Replacement sent', $data['body_en']);
        $this->assertNotEmpty($data['title_ar']);
    }

    public function test_out_of_stock_fulfilment_notifies_flagging_rep_once_and_idempotently(): void
    {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $service = app(OutOfStockService::class);

        $request = $service->raise($this->rep, $product->id, 5.0);

        $service->resolve($request, $this->manager->id);
        $service->resolve($request->refresh(), $this->manager->id);

        $this->assertSame(1, $this->rep->notifications()->count());
    }

    public function test_rolled_back_resolution_never_notifies(): void
    {
        $complaint = $this->makeComplaint();

        try {
            DB::transaction(function () use ($complaint): void {
                app(ComplaintService::class)->resolve($complaint, $this->manager->id, 'Oops');

                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, $this->rep->notifications()->count());
    }

    public function test_customer_approval_notifies_creating_rep(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'pending',
            'added_by' => $this->rep->id,
        ]);

        Livewire::actingAs($this->manager)
            ->test(ListCustomers::class)
            ->callTableAction('approve', $customer);

        $this->assertSame(1, $this->rep->notifications()->count());

        $data = $this->rep->notifications()->first()->data;
        $this->assertSame('info', $data['severity']);
    }

    public function test_customer_rejection_notifies_creating_rep_with_reason(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'pending',
            'added_by' => $this->rep->id,
        ]);

        Livewire::actingAs($this->manager)
            ->test(ListCustomers::class)
            ->callTableAction('reject', $customer, ['rejection_reason' => 'Duplicate account']);

        $this->assertSame(1, $this->rep->notifications()->count());
        $this->assertSame(0, $this->otherRep->notifications()->count());

        $data = $this->rep->notifications()->first()->data;
        $this->assertSame('critical', $data['severity']);
        $this->assertStringContainsString('Duplicate account', $data['body_en']);
        $this->assertStringContainsString('Duplicate account', $data['body_ar']);
    }

    public function test_bell_badge_shows_unread_count_on_rep_pages(): void
    {
        $complaint = $this->makeComplaint();
        app(ComplaintService::class)->resolve($complaint, $this->manager->id, 'Done');

        $this->actingAs($this->rep)
            ->get('/app')
            ->assertOk()
            ->assertSee(__('app.notifications'))
            ->assertSee('/app/notifications', false);
    }

    public function test_notifications_page_lists_and_marks_read_persistently(): void
    {
        $complaint = $this->makeComplaint();
        app(ComplaintService::class)->resolve($complaint, $this->manager->id, 'Handled');

        $this->assertSame(1, $this->rep->unreadNotifications()->count());

        Livewire::actingAs($this->rep)
            ->test(\App\Livewire\App\Notifications::class)
            ->assertSee(__('app.notifications'));

        $this->assertSame(0, $this->rep->fresh()->unreadNotifications()->count());
        $this->assertSame(1, $this->rep->fresh()->notifications()->whereNotNull('read_at')->count());
    }

    public function test_notifications_page_shows_empty_state(): void
    {
        Livewire::actingAs($this->rep)
            ->test(\App\Livewire\App\Notifications::class)
            ->assertSee(__('app.no_notifications'));
    }
}
