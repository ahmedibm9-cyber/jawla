<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Batch;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Contracts\StockService as StockServiceContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use PDOException;

class StockService implements StockServiceContract
{
    private const MAX_DEADLOCK_RETRIES = 3;

    public function decrement(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId = null): StockMovement
    {
        return $this->move($warehouseId, $productId, $batchId, -$qty, $reason, $ref, $userId);
    }

    public function increment(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId = null): StockMovement
    {
        return $this->move($warehouseId, $productId, $batchId, $qty, $reason, $ref, $userId);
    }

    public function transfer(int $fromWarehouseId, int $toWarehouseId, int $productId, ?int $batchId, float $qty, Model $ref, ?int $userId = null): StockMovement
    {
        return $this->withDeadlockRetry(function () use ($fromWarehouseId, $toWarehouseId, $productId, $batchId, $qty, $ref, $userId): StockMovement {
            return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $batchId, $qty, $ref, $userId): StockMovement {
                $this->decrement($fromWarehouseId, $productId, $batchId, $qty, StockReason::TransferOut, $ref, $userId);

                return $this->increment($toWarehouseId, $productId, $batchId, $qty, StockReason::TransferIn, $ref, $userId);
            });
        });
    }

    public function balance(int $warehouseId, int $productId, ?int $batchId = null): float
    {
        $query = Stock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId);

        if ($batchId !== null) {
            $query->where('batch_id', $batchId);
        }

        return (float) $query->sum('quantity');
    }

    public function reconcile(int $warehouseId, int $productId, ?int $batchId, float $countedQty, string $reason, int $userId, ?float $expectedQty = null, ?Model $reference = null): StockMovement
    {
        return $this->withDeadlockRetry(function () use ($warehouseId, $productId, $batchId, $countedQty, $userId, $expectedQty, $reference): StockMovement {
            return DB::transaction(function () use ($warehouseId, $productId, $batchId, $countedQty, $userId, $expectedQty, $reference): StockMovement {
                if ($batchId !== null) {
                    $this->validateBatchEligibility($warehouseId, $productId, $batchId, 0);
                }

                $this->lockWarehouse($warehouseId);
                $stock = $this->lockOrCreateStock($warehouseId, $productId, $batchId);

                $current = (float) $stock->quantity;
                if ($expectedQty !== null
                    && bccomp(number_format($current, 3, '.', ''), number_format($expectedQty, 3, '.', ''), 3) !== 0) {
                    throw new DomainException(
                        'Stock changed after the count snapshot. Recount before applying an adjustment.'
                    );
                }
                $difference = $countedQty - $current;
                $stock->quantity = (string) $countedQty;
                $stock->save();

                return StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity_change' => $difference,
                    'reason' => StockReason::Adjustment,
                    'reference_type' => $reference?->getMorphClass() ?? 'reconciliation',
                    'reference_id' => $reference?->getKey() ?? 0,
                    'user_id' => $userId,
                ]);
            });
        });
    }

    private function move(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId): StockMovement
    {
        return $this->withDeadlockRetry(function () use ($warehouseId, $productId, $batchId, $qty, $reason, $ref, $userId): StockMovement {
            return DB::transaction(function () use ($warehouseId, $productId, $batchId, $qty, $reason, $ref, $userId): StockMovement {
                // Lock warehouse row first — consistent lock ordering with reconcile()
                // prevents deadlock and serializes concurrent stock creation for the
                // same warehouse.
                $this->lockWarehouse($warehouseId);

                if ($batchId !== null) {
                    $this->validateBatchEligibility($warehouseId, $productId, $batchId, $qty);
                }

                $stock = $this->lockOrCreateStock($warehouseId, $productId, $batchId);

                $newQty = bcadd((string) $stock->quantity, number_format($qty, 3, '.', ''), 3);

                if (bccomp($newQty, '0', 3) < 0) {
                    throw new InsufficientStockException(
                        'errors.stock.insufficient',
                        ['product' => (string) $productId, 'available' => (string) $stock->quantity],
                    );
                }

                $stock->quantity = $newQty;
                $stock->save();

                return StockMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity_change' => $qty,
                    'reason' => $reason,
                    'reference_type' => $ref::class,
                    'reference_id' => $ref->id, // @phpstan-ignore-line property.notFound
                    'user_id' => $userId,
                ]);
            });
        });
    }

    /**
     * Lock the warehouse row to prevent concurrent stock creation races.
     */
    private function lockWarehouse(int $warehouseId): void
    {
        DB::table('warehouses')->where('id', $warehouseId)->lockForUpdate()->first();
    }

    /**
     * Lock an existing stock row or create one under the warehouse lock.
     *
     * The warehouse lock (held by the caller) serializes concurrent creation,
     * so the TOCTOU gap between SELECT and INSERT is safe.
     */
    private function lockOrCreateStock(int $warehouseId, int $productId, ?int $batchId): Stock
    {
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        try {
            return Stock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'quantity' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another transaction created the row between our SELECT and INSERT
            // (possible in PostgreSQL even under warehouse lock if a prior
            // transaction released it). Re-fetch with lock.
            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                return $stock;
            }

            // Should not reach here — rethrow
            throw new DomainException('errors.stock.create_race');
        }
    }

    private function validateBatchEligibility(int $warehouseId, int $productId, int $batchId, float $qty): void
    {
        $batch = Batch::with('product')->find($batchId);

        if (! $batch) {
            throw new DomainException('errors.batch.not_found', ['batch' => (string) $batchId]);
        }

        if (! $batch->is_active) {
            throw new DomainException('errors.batch.inactive', ['batch' => $batch->batch_number]);
        }

        if ($batch->isExpired()) {
            throw new DomainException('errors.batch.expired', ['batch' => $batch->batch_number]);
        }

        if ($batch->product_id !== $productId) {
            throw new DomainException('errors.batch.product_mismatch');
        }

        // Cross-company check: batch's product company must match warehouse company
        $warehouse = Warehouse::find($warehouseId);
        if ($batch->product->company_id !== $warehouse->company_id) {
            throw new DomainException('errors.batch.cross_company');
        }

        // FEFO guard on decrements: reject if a lower-expiry batch has stock
        if ($qty < 0) {
            $fefoBatch = Batch::fefoForProduct($productId)->first();
            if ($fefoBatch && $fefoBatch->id !== $batchId) {
                $fefoStock = Stock::where('batch_id', $fefoBatch->id)->sum('quantity');
                if ($fefoStock > 0) {
                    throw new DomainException('errors.batch.fefo_violation', [
                        'expected' => $fefoBatch->batch_number,
                        'given' => $batch->batch_number,
                    ]);
                }
            }
        }
    }

    /**
     * Retry a closure on PostgreSQL deadlock (SQLSTATE 40P01).
     *
     * When StockService methods are called from within an outer DB::transaction()
     * (e.g. InvoiceService::create), a deadlock here must NOT roll back the outer
     * transaction — that would leave the system in an inconsistent state where
     * stock is moved but financial records are not.
     *
     * Strategy: if the StockService itself owns the transaction (transfer), retry
     * the full closure. If nested inside a caller's transaction, let the exception
     * propagate so the caller's transaction can decide whether to retry.
     */
    private function withDeadlockRetry(\Closure $action): mixed
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return $action();
            } catch (PDOException $e) {
                // PostgreSQL deadlock: SQLSTATE 40P01
                if ($attempts < self::MAX_DEADLOCK_RETRIES
                    && str_contains($e->getMessage(), '40P01')
                    && DB::transactionLevel() <= 1) {
                    // Only retry when we own the top-level transaction
                    // (level 1 = this is the outermost transaction).
                    // When nested (level > 1), let the caller handle it.
                    DB::rollBack();

                    continue;
                }

                throw $e;
            }
        }
    }
}
