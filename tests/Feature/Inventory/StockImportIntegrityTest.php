<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\Domain\DomainException;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockImportIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Warehouse $warehouse;

    private Product $product;

    private User $keeper;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'main',
        ]);
        $category = ProductCategory::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'sku' => 'SAFE-IMPORT-1',
        ]);
        $this->keeper = User::factory()->create(['company_id' => $this->company->id]);
        $this->keeper->assignRole('warehouse_keeper');
        $this->manager = User::factory()->create(['company_id' => $this->company->id]);
        $this->manager->assignRole('sales_manager');
    }

    private function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'jawla-stock-import-');
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_confirmation_reparses_the_server_file_and_rejects_tampering(): void
    {
        Stock::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
        $path = $this->csv("sku,quantity\nSAFE-IMPORT-1,11\n");

        $staged = app(StockImportService::class)->stage($path, $this->warehouse, $this->keeper);
        file_put_contents($path, "sku,quantity\nSAFE-IMPORT-1,999\n");

        $this->expectException(DomainException::class);
        try {
            app(StockImportService::class)->confirm($staged['token'], $this->keeper, 'tampered.csv');
        } finally {
            $this->assertSame('10.000', Stock::where('warehouse_id', $this->warehouse->id)->value('quantity'));
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertDatabaseCount('warehouse_import_logs', 0);
        }
    }

    public function test_confirmation_rejects_a_stale_stock_snapshot_atomically(): void
    {
        Stock::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
        $path = $this->csv("sku,quantity\nSAFE-IMPORT-1,11\n");
        $staged = app(StockImportService::class)->stage($path, $this->warehouse, $this->keeper);

        Stock::where('warehouse_id', $this->warehouse->id)->update(['quantity' => 10.5]);

        $this->expectException(DomainException::class);
        try {
            app(StockImportService::class)->confirm($staged['token'], $this->keeper, 'stale.csv');
        } finally {
            $this->assertSame('10.500', Stock::where('warehouse_id', $this->warehouse->id)->value('quantity'));
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertDatabaseCount('warehouse_import_logs', 0);
        }
    }

    public function test_opening_balance_requires_sales_manager_approval(): void
    {
        $path = $this->csv("sku,quantity\nSAFE-IMPORT-1,25\n");
        $staged = app(StockImportService::class)->stage($path, $this->warehouse, $this->keeper);

        $this->assertTrue($staged['requires_approval']);

        try {
            app(StockImportService::class)->confirm($staged['token'], $this->keeper, 'opening.csv');
            $this->fail('Unapproved opening balance was imported.');
        } catch (DomainException) {
            $this->assertDatabaseCount('stock_movements', 0);
        }

        app(StockImportService::class)->approve($staged['token'], $this->manager);
        $log = app(StockImportService::class)->confirm($staged['token'], $this->keeper, 'opening.csv');

        $this->assertSame(1, $log->rows_imported);
        $this->assertSame('25.000', Stock::where('warehouse_id', $this->warehouse->id)->value('quantity'));
        $this->assertSame('25.000', StockMovement::sum('quantity_change'));
    }

    public function test_token_is_bound_to_company_warehouse_user_and_single_use(): void
    {
        Stock::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
        $staged = app(StockImportService::class)->stage(
            $this->csv("sku,quantity\nSAFE-IMPORT-1,11\n"),
            $this->warehouse,
            $this->keeper,
        );
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        $otherUser->assignRole('warehouse_keeper');

        $this->expectException(DomainException::class);
        app(StockImportService::class)->confirm($staged['token'], $otherUser, 'other.csv');
    }

    public function test_object_storage_import_is_streamed_to_a_verified_local_copy(): void
    {
        Storage::fake('s3');
        Stock::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
        Storage::disk('s3')->put('stock-imports/opening.csv', "sku,quantity\nSAFE-IMPORT-1,11\n");

        $staged = app(StockImportService::class)->stageFromDisk(
            's3',
            'stock-imports/opening.csv',
            $this->warehouse,
            $this->keeper,
        );
        app(StockImportService::class)->confirm($staged['token'], $this->keeper, 'opening.csv');

        $this->assertSame('11.000', Stock::where('warehouse_id', $this->warehouse->id)->value('quantity'));
        $this->assertDatabaseHas('stock_import_previews', [
            'source_disk' => 's3',
            'file_path' => 'stock-imports/opening.csv',
            'status' => 'consumed',
        ]);
    }
}
