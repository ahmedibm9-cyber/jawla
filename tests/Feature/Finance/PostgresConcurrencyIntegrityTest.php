<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostgresConcurrencyIntegrityTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();
        parent::tearDown();
    }

    public function test_two_real_connections_cannot_both_sell_the_final_unit(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->create([
            'company_id' => $company->id,
            'type' => 'van',
            'user_id' => $user->id,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '1.000',
        ]);

        $results = $this->race('stock', [
            $company->id, $warehouse->id, $product->id, $user->id,
        ]);

        $this->assertSame([0, 2], $results);
        $this->assertSame('0.000', Stock::whereKey(Stock::max('id'))->value('quantity'));
        $this->assertSame(1, StockMovement::where('warehouse_id', $warehouse->id)->count());
    }

    public function test_two_real_connections_with_one_payment_intent_post_once(): void
    {
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('sales_rep');
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'balance' => '100.00',
        ]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => InvoiceStatus::Issued,
            'total' => '100.00',
            'remaining_amount' => '100.00',
        ]);
        $intentId = 'concurrency-'.Str::uuid();

        $results = $this->race('payment', [
            $company->id, $user->id, $customer->id, $invoice->id, $intentId,
        ]);

        $this->assertSame([0, 0], $results);
        $this->assertSame(1, Payment::where('intent_id', $intentId)->count());
        $this->assertSame('50.00', $invoice->fresh()->remaining_amount);
        $this->assertSame('50.00', $customer->fresh()->balance);
    }

    public function test_sale_racing_stock_count_cannot_overwrite_the_sale(): void
    {
        [$company, $user, $warehouse, $product] = $this->stockFixture();
        $results = $this->racePair('stock', 'count', [
            $company->id, $warehouse->id, $product->id, $user->id,
        ]);

        $this->assertContains($results, [[0, 0], [0, 2]]);
        $quantity = Stock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)->value('quantity');
        $this->assertSame($results === [0, 0] ? '1.000' : '0.000', $quantity);
    }

    public function test_return_racing_sale_preserves_both_locked_movements(): void
    {
        [$company, $user, $warehouse, $product] = $this->stockFixture();
        $results = $this->racePair('stock', 'return', [
            $company->id, $warehouse->id, $product->id, $user->id,
        ]);

        $this->assertSame([0, 0], $results);
        $this->assertSame('1.000', Stock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)->value('quantity'));
        $this->assertSame('0.000', StockMovement::where('warehouse_id', $warehouse->id)->sum('quantity_change'));
    }

    private function race(string $mode, array $arguments): array
    {
        return $this->racePair($mode, $mode, $arguments);
    }

    private function racePair(string $firstMode, string $secondMode, array $arguments): array
    {
        $barrier = tempnam(sys_get_temp_dir(), 'jawla-race-');
        unlink($barrier);
        $command = static fn (string $mode): array => [
            PHP_BINARY,
            base_path('tests/Support/ConcurrentMutationWorker.php'),
            $mode,
            $barrier,
            ...array_map('strval', $arguments),
        ];
        $connection = config('database.default');
        $database = config("database.connections.{$connection}");
        $environment = array_filter([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $database['host'] ?? null,
            'DB_PORT' => isset($database['port']) ? (string) $database['port'] : null,
            'DB_DATABASE' => $database['database'] ?? null,
            'DB_USERNAME' => $database['username'] ?? null,
            'DB_PASSWORD' => $database['password'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);
        $first = new Process($command($firstMode), base_path(), $environment);
        $second = new Process($command($secondMode), base_path(), $environment);
        $first->start();
        $second->start();
        usleep(150_000);
        touch($barrier);
        $first->wait();
        $second->wait();
        @unlink($barrier);
        if (
            $first->getExitCode() === 255
            || $second->getExitCode() === 255
            || ($firstMode !== $secondMode && $first->getExitCode() !== 0)
        ) {
            $this->fail(trim(
                "{$firstMode} [{$first->getExitCode()}]: {$first->getErrorOutput()}\n"
                ."{$secondMode} [{$second->getExitCode()}]: {$second->getErrorOutput()}"
            ));
        }

        $codes = [$first->getExitCode(), $second->getExitCode()];
        sort($codes);

        return $codes;
    }

    private function stockFixture(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $warehouse = Warehouse::factory()->create([
            'company_id' => $company->id,
            'type' => 'van',
            'user_id' => $user->id,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => '1.000',
        ]);

        return [$company, $user, $warehouse, $product];
    }
}
