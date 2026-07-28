<?php

namespace Tests\Unit\Services;

use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;
use App\Services\BatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fefo_batch_returns_earliest_active_expiry(): void
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id, 'track_batch' => true]);

        $batch1 = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => now()->addDays(60),
            'is_active' => true,
        ]);
        $batch2 = Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => now()->addDays(10),
            'is_active' => true,
        ]);

        $fefo = app(BatchService::class)->fefoBatch($product->id);

        $this->assertNotNull($fefo);
        $this->assertSame($batch2->id, $fefo->id);
    }

    public function test_fefo_batch_returns_null_for_non_tracked_product(): void
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id, 'track_batch' => false]);

        $this->assertNull(app(BatchService::class)->fefoBatch($product->id));
    }

    public function test_expiring_soon_returns_batches_within_window(): void
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);

        Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => now()->addDays(15),
            'is_active' => true,
        ]);
        Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => now()->addDays(90),
            'is_active' => true,
        ]);

        $expiring = app(BatchService::class)->expiringSoon($company->id, 30);

        $this->assertCount(1, $expiring);
    }

    public function test_expired_returns_past_expiry_batches(): void
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);

        Batch::factory()->expired()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        Batch::factory()->create([
            'product_id' => $product->id,
            'expiry_date' => now()->addYear(),
            'is_active' => true,
        ]);

        $expired = app(BatchService::class)->expired($company->id);

        $this->assertCount(1, $expired);
    }

    public function test_expired_excludes_inactive_batches(): void
    {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);

        Batch::factory()->expired()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);

        $expired = app(BatchService::class)->expired($company->id);

        $this->assertCount(0, $expired);
    }
}
