<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Stock;
use App\Models\StockMovement;
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

        return (float) ($query->sum('quantity') ?? 0);
    }

    public function reconcile(int $warehouseId, int $productId, ?int $batchId, float $countedQty, string $reason, int $userId): StockMovement
    {
        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $countedQty, $userId): StockMovement {
            $current = $this->balance($warehouseId, $productId, $batchId);
            $difference = $countedQty - $current;

            $stock = Stock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_id' => $batchId,
            ], ['quantity' => 0]);

            $stock->quantity += $difference;
            $stock->save();

            return StockMovement::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_id' => $batchId,
                'quantity_change' => $difference,
                'reason' => StockReason::Adjustment,
                'reference_type' => 'reconciliation',
                'reference_id' => 0,
                'user_id' => $userId,
            ]);
        });
    }

    private function move(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId): StockMovement
    {
        return DB::transaction(function () use ($warehouseId, $productId, $batchId, $qty, $reason, $ref, $userId): StockMovement {
            $stock = Stock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'batch_id' => $batchId,
            ], ['quantity' => 0]);

            $newQty = $stock->quantity + $qty;

            if ($newQty < 0) {
                throw new InsufficientStockException(
                    'errors.stock.insufficient',
                    ['product' => $productId, 'available' => $stock->quantity],
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
                'reference_id' => $ref->id,
                'user_id' => $userId,
            ]);
        });
    }
}
