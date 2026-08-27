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
use Illuminate\Support\Facades\DB;

class StockService implements StockServiceContract
{
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
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $batchId, $qty, $ref, $userId): StockMovement {
            $this->decrement($fromWarehouseId, $productId, $batchId, $qty, StockReason::TransferOut, $ref, $userId);

            return $this->increment($toWarehouseId, $productId, $batchId, $qty, StockReason::TransferIn, $ref, $userId);
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
        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $countedQty, $userId, $expectedQty, $reference): StockMovement {
            if ($batchId !== null) {
                $this->validateBatchEligibility($warehouseId, $productId, $batchId, 0);
            }

            DB::table('warehouses')->where('id', $warehouseId)->lockForUpdate()->first();
            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->first();
            if (! $stock) {
                $stock = new Stock([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity' => 0,
                ]);
            }

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
    }

    private function move(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId): StockMovement
    {
        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $qty, $reason, $ref, $userId): StockMovement {
            if ($batchId !== null) {
                $this->validateBatchEligibility($warehouseId, $productId, $batchId, $qty);
            }

            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = Stock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'quantity' => 0,
                ]);
            }

            $newQty = (float) $stock->quantity + $qty;

            if ($newQty < 0) {
                throw new InsufficientStockException(
                    'errors.stock.insufficient',
                    ['product' => (string) $productId, 'available' => (string) $stock->quantity],
                );
            }

            $stock->quantity = (string) $newQty;
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
}
