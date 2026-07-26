<?php

namespace Tests\Unit\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseImportLog;
use App\Services\StockImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $company->id]);
        $this->user->assignRole(['warehouse_keeper', 'sales_manager']);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'type' => 'main']);
        $category = ProductCategory::factory()->create(['company_id' => $company->id]);
        $this->product = Product::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'sku' => 'SKU-IMP-1',
        ]);
    }

    private function fixture(string $csv): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, $csv);

        return $path;
    }

    public function test_preview_validates_without_mutating_stock(): void
    {
        $preview = app(StockImportService::class)->preview(
            $this->fixture("sku,quantity\nSKU-IMP-1,50\n"),
            $this->warehouse,
        );

        $this->assertCount(1, $preview['valid']);
        $this->assertSame([], $preview['errors']);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_preview_rejects_unknown_duplicate_and_invalid_rows(): void
    {
        $preview = app(StockImportService::class)->preview(
            $this->fixture("sku,quantity\nSKU-NOPE,10\nSKU-IMP-1,-5\nSKU-IMP-1,abc\n"),
            $this->warehouse,
        );

        $this->assertSame([], $preview['valid']);
        $this->assertCount(3, $preview['errors']);
    }

    public function test_preview_rejects_wrong_headings(): void
    {
        $preview = app(StockImportService::class)->preview(
            $this->fixture("code,qty\nSKU-IMP-1,10\n"),
            $this->warehouse,
        );

        $this->assertFalse($preview['headings_ok']);
        $this->assertSame([], $preview['valid']);
    }

    public function test_apply_creates_matching_stock_movements(): void
    {
        $service = app(StockImportService::class);
        $staged = $service->stage(
            $this->fixture("sku,quantity\nSKU-IMP-1,75.5\n"),
            $this->warehouse,
            $this->user,
        );
        $service->approve($staged['token'], $this->user);

        $log = $service->confirm($staged['token'], $this->user, 'test.csv');

        $this->assertSame(1, $log->rows_imported);
        $this->assertEquals(75.5, (float) StockMovement::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->sum('quantity_change'));
        $this->assertEquals(75.5, (float) $this->product->stocks()->sum('quantity'));
    }

    public function test_reimporting_same_file_is_blocked_by_checksum(): void
    {
        $service = app(StockImportService::class);
        $path = $this->fixture("sku,quantity\nSKU-IMP-1,10\n");
        $first = $service->stage($path, $this->warehouse, $this->user);
        $service->approve($first['token'], $this->user);
        $service->confirm($first['token'], $this->user, 'test.csv');
        $second = $service->stage($path, $this->warehouse, $this->user);

        $this->expectException(DomainException::class);

        try {
            $service->confirm($second['token'], $this->user, 'test.csv');
        } finally {
            $this->assertSame(1, WarehouseImportLog::count());
        }
    }

    public function test_cross_company_products_are_rejected(): void
    {
        $other = Company::factory()->create();
        $otherCategory = ProductCategory::factory()->create(['company_id' => $other->id]);
        Product::factory()->create([
            'company_id' => $other->id,
            'category_id' => $otherCategory->id,
            'sku' => 'SKU-OTHER',
        ]);

        $preview = app(StockImportService::class)->preview(
            $this->fixture("sku,quantity\nSKU-OTHER,10\n"),
            $this->warehouse,
        );

        $this->assertSame([], $preview['valid']);
        $this->assertCount(1, $preview['errors']);
    }
}
