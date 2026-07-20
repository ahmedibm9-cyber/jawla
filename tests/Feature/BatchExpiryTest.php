<?php

namespace Tests\Feature;

use App\Filament\Resources\BatchResource\Pages\ListBatches;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\BatchService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BatchExpiryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_expiry_state_helpers(): void
    {
        $expired = Batch::factory()->expired()->create(['product_id' => $this->product->id]);
        $soon = Batch::factory()->expiringInDays(10)->create(['product_id' => $this->product->id]);
        $fresh = Batch::factory()->create(['product_id' => $this->product->id, 'expiry_date' => now()->addYear()]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($soon->isExpired());
        $this->assertTrue($soon->expiresWithinDays(30));
        $this->assertFalse($fresh->expiresWithinDays(30));
    }

    public function test_fefo_selects_earliest_unexpired_batch(): void
    {
        Batch::factory()->create(['product_id' => $this->product->id, 'batch_number' => 'LATE', 'expiry_date' => now()->addMonths(6)]);
        Batch::factory()->create(['product_id' => $this->product->id, 'batch_number' => 'SOON', 'expiry_date' => now()->addMonth()]);
        Batch::factory()->expired()->create(['product_id' => $this->product->id, 'batch_number' => 'DEAD']);

        $picked = app(BatchService::class)->fefoBatch($this->product->id);

        $this->assertNotNull($picked);
        $this->assertSame('SOON', $picked->batch_number, 'FEFO must skip expired and pick the earliest live expiry');
    }

    public function test_fefo_ignores_inactive_batches(): void
    {
        Batch::factory()->create(['product_id' => $this->product->id, 'batch_number' => 'OFF', 'expiry_date' => now()->addWeek(), 'is_active' => false]);
        Batch::factory()->create(['product_id' => $this->product->id, 'batch_number' => 'ON', 'expiry_date' => now()->addMonth()]);

        $this->assertSame('ON', app(BatchService::class)->fefoBatch($this->product->id)?->batch_number);
    }

    public function test_expiring_soon_and_expired_queries_are_company_scoped(): void
    {
        $otherCompany = Company::factory()->create();
        $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id]);

        Batch::factory()->expiringInDays(15)->create(['product_id' => $this->product->id]);
        Batch::factory()->expiringInDays(15)->create(['product_id' => $otherProduct->id]);
        Batch::factory()->expired()->create(['product_id' => $this->product->id]);

        $service = app(BatchService::class);
        $this->assertCount(1, $service->expiringSoon($this->company->id, 30));
        $this->assertCount(1, $service->expired($this->company->id));
    }

    public function test_admin_resource_lists_only_this_company_batches(): void
    {
        $otherCompany = Company::factory()->create();
        $mine = Batch::factory()->create(['product_id' => $this->product->id]);
        $theirs = Batch::factory()->create(['product_id' => Product::factory()->create(['company_id' => $otherCompany->id])->id]);

        $keeper = User::factory()->create(['company_id' => $this->company->id]);
        $keeper->assignRole('warehouse_keeper');

        Livewire::actingAs($keeper)
            ->test(ListBatches::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_role_gating(): void
    {
        $keeper = User::factory()->create(['company_id' => $this->company->id]);
        $keeper->assignRole('warehouse_keeper');
        $rep = User::factory()->create(['company_id' => $this->company->id]);
        $rep->assignRole('rep');

        $this->assertTrue($keeper->can('viewAny', Batch::class));
        $this->assertTrue($keeper->can('create', Batch::class));
        $this->assertFalse($rep->can('viewAny', Batch::class));
    }
}
