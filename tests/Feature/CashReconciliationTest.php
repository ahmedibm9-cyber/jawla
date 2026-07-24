<?php

namespace Tests\Feature;

use App\Filament\Resources\CashReconciliationResource\Pages\ListCashReconciliations;
use App\Livewire\App\CashReconcile;
use App\Models\CashBox;
use App\Models\CashReconciliation;
use App\Models\Company;
use App\Models\User;
use App\Services\CashReconciliationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashReconciliationTest extends TestCase
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
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole($role);

        return $user;
    }

    private function service(): CashReconciliationService
    {
        return app(CashReconciliationService::class);
    }

    public function test_submit_snapshots_expected_balance_and_computes_variance(): void
    {
        CashBox::create(['company_id' => $this->company->id, 'user_id' => $this->rep->id, 'balance' => 1000]);

        $recon = $this->service()->submit($this->company->id, $this->rep->id, 950.0, 'short 50');

        $this->assertSame(1000.0, (float) $recon->expected_amount);
        $this->assertSame(950.0, (float) $recon->counted_amount);
        $this->assertSame(-50.0, (float) $recon->variance);
        $this->assertSame('pending', $recon->status);
        $this->assertSame('short 50', $recon->notes);
    }

    public function test_submit_with_no_cashbox_treats_expected_as_zero(): void
    {
        $recon = $this->service()->submit($this->company->id, $this->rep->id, 0.0);

        $this->assertSame(0.0, (float) $recon->expected_amount);
        $this->assertSame(0.0, (float) $recon->variance);
    }

    public function test_submit_rejects_a_rep_from_another_company_before_creating_a_record(): void
    {
        $foreignRep = User::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->expectException(\App\Exceptions\Domain\DomainException::class);
        try {
            $this->service()->submit($this->company->id, $foreignRep->id, 100.0);
        } finally {
            $this->assertDatabaseCount('cash_reconciliations', 0);
        }
    }

    public function test_manager_approves_reconciliation(): void
    {
        CashBox::create(['company_id' => $this->company->id, 'user_id' => $this->rep->id, 'balance' => 500]);
        $recon = $this->service()->submit($this->company->id, $this->rep->id, 500.0);

        $this->service()->approve($recon, $this->manager->id, 'counted with rep');

        $recon->refresh();
        $this->assertSame('approved', $recon->status);
        $this->assertSame($this->manager->id, $recon->reviewed_by);
        $this->assertNotNull($recon->reviewed_at);
        $this->assertSame('counted with rep', $recon->review_notes);
    }

    public function test_manager_flags_reconciliation_with_variance(): void
    {
        CashBox::create(['company_id' => $this->company->id, 'user_id' => $this->rep->id, 'balance' => 500]);
        $recon = $this->service()->submit($this->company->id, $this->rep->id, 420.0, 'lost receipt');

        $this->service()->flag($recon, $this->manager->id, 'investigate 80 short');

        $recon->refresh();
        $this->assertSame('flagged', $recon->status);
        $this->assertSame(-80.0, (float) $recon->variance);
    }

    public function test_reconciliation_cannot_be_reviewed_twice(): void
    {
        $recon = $this->service()->submit($this->company->id, $this->rep->id, 0.0);
        $this->service()->approve($recon, $this->manager->id);

        $this->expectException(\RuntimeException::class);
        $this->service()->flag($recon, $this->manager->id);
    }

    public function test_reconciliation_does_not_mutate_the_cashbox(): void
    {
        $box = CashBox::create(['company_id' => $this->company->id, 'user_id' => $this->rep->id, 'balance' => 700]);
        $recon = $this->service()->submit($this->company->id, $this->rep->id, 650.0);
        $this->service()->approve($recon, $this->manager->id);

        $this->assertSame(700.0, (float) $box->fresh()->balance);
    }

    public function test_rep_page_submits_reconciliation(): void
    {
        CashBox::create(['company_id' => $this->company->id, 'user_id' => $this->rep->id, 'balance' => 300]);

        Livewire::actingAs($this->rep)
            ->test(CashReconcile::class)
            ->assertSee(number_format(300, 2))
            ->set('counted_amount', 300)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('success', true);

        $this->assertDatabaseHas('cash_reconciliations', [
            'user_id' => $this->rep->id,
            'counted_amount' => 300,
            'variance' => 0,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_via_filament_action(): void
    {
        CashBox::create(['company_id' => $this->company->id, 'user_id' => $this->rep->id, 'balance' => 200]);
        $recon = $this->service()->submit($this->company->id, $this->rep->id, 180.0);

        Livewire::actingAs($this->manager)
            ->test(ListCashReconciliations::class)
            ->callTableAction('flag', $recon, ['review_notes' => '20 short']);

        $this->assertSame('flagged', $recon->fresh()->status);
    }

    public function test_rep_cannot_access_admin_resource(): void
    {
        $this->assertFalse($this->rep->can('viewAny', CashReconciliation::class));
        $this->assertTrue($this->manager->can('viewAny', CashReconciliation::class));
    }
}
