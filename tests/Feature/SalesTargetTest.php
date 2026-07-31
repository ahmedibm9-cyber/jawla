<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\SalesTargetResource\Pages\CreateSalesTarget;
use App\Filament\Resources\SalesTargetResource\Pages\ListSalesTargets;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\AttainmentService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesTargetTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->rep = $this->makeUser('rep');
        $this->manager = $this->makeUser('sales_manager');
    }

    private function makeUser(string $role): User
    {
        $u = User::factory()->create(['company_id' => $this->company->id]);
        $u->assignRole($role);

        return $u;
    }

    private function invoiceFor(User $rep, float $total, string $postingDate, InvoiceStatus $status = InvoiceStatus::Submitted): Invoice
    {
        return Invoice::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $rep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
            'total' => $total,
            'status' => $status,
            'posting_date' => $postingDate,
        ]);
    }

    private function monthTarget(float $amount): SalesTarget
    {
        return SalesTarget::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'metric' => 'sales_amount',
            'target_amount' => $amount,
        ]);
    }

    public function test_attainment_sums_in_period_non_cancelled_invoices(): void
    {
        $target = $this->monthTarget(10000);
        $this->invoiceFor($this->rep, 3000, now()->startOfMonth()->addDay()->toDateString());
        $this->invoiceFor($this->rep, 2000, now()->endOfMonth()->subDay()->toDateString());
        // excluded: cancelled, out-of-period, other rep
        $this->invoiceFor($this->rep, 9999, now()->toDateString(), InvoiceStatus::Cancelled);
        $this->invoiceFor($this->rep, 5000, now()->subMonthsNoOverflow(2)->toDateString());
        $this->invoiceFor($this->manager, 4000, now()->toDateString());

        $a = app(AttainmentService::class)->attainment($target);

        $this->assertSame(5000.0, $a['actual']);
        $this->assertSame(10000.0, $a['target']);
        $this->assertSame(5000.0, $a['remaining']);
        $this->assertSame(50.0, $a['percent']);
    }

    public function test_attainment_caps_remaining_at_zero_when_exceeded(): void
    {
        $target = $this->monthTarget(1000);
        $this->invoiceFor($this->rep, 1500, now()->toDateString());

        $a = app(AttainmentService::class)->attainment($target);
        $this->assertSame(150.0, $a['percent']);
        $this->assertSame(0.0, $a['remaining']);
    }

    public function test_current_target_for_rep_returns_target_covering_today(): void
    {
        $this->monthTarget(8000);
        $found = app(AttainmentService::class)->currentTargetForRep($this->rep->id);
        $this->assertNotNull($found);
        $this->assertSame(8000.0, (float) $found->target_amount);

        $none = app(AttainmentService::class)->currentTargetForRep($this->manager->id);
        $this->assertNull($none);
    }

    public function test_zero_target_yields_zero_percent_not_division_error(): void
    {
        $target = $this->monthTarget(0);
        $this->invoiceFor($this->rep, 500, now()->toDateString());
        $this->assertSame(0.0, app(AttainmentService::class)->attainment($target)['percent']);
    }

    public function test_manager_can_list_targets_rep_cannot(): void
    {
        $this->assertTrue($this->manager->can('viewAny', SalesTarget::class));
        $this->assertFalse($this->rep->can('viewAny', SalesTarget::class));
        $this->assertTrue($this->manager->can('create', SalesTarget::class));
    }

    public function test_manager_creates_target_via_filament(): void
    {
        app(ActiveCompanyContext::class)->setFromUser($this->manager);

        Livewire::actingAs($this->manager)
            ->test(CreateSalesTarget::class)
            ->fillForm([
                'user_id' => $this->rep->id,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'target_amount' => 25000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sales_targets', [
            'user_id' => $this->rep->id,
            'target_amount' => 25000,
            'created_by' => $this->manager->id,
        ]);
    }

    public function test_targets_are_company_scoped_in_the_resource(): void
    {
        $mine = $this->monthTarget(1000);
        $other = Company::factory()->create();
        $otherRep = User::factory()->create(['company_id' => $other->id]);
        $theirs = SalesTarget::create([
            'company_id' => $other->id, 'user_id' => $otherRep->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'metric' => 'sales_amount', 'target_amount' => 2000,
        ]);

        // Mirror the panel middleware: the active-company scope is bound to the
        // signed-in user's company, so a manager's table excludes other tenants.
        app(ActiveCompanyContext::class)->setFromUser($this->manager);

        Livewire::actingAs($this->manager)
            ->test(ListSalesTargets::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        app(ActiveCompanyContext::class)->disable();
    }
}
