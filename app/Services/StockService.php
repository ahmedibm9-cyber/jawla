<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Record a stock movement (sale, return, transfer, adjustment, etc.)
     * ALWAYS: updates stocks.quantity AND writes stock_movements row
     * NEVER: call this outside DB::transaction()
     */
    public function recordMovement(
        Warehouse $warehouse,
        int $productId,
        int $quantityChange,
        string $reason,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        // Get or create the stock record
        $stock = Stock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->where('stock_type', 'regular')
            ->firstOrCreate([
                'warehouse_id' => $warehouse->id,
                'product_id' => $productId,
                'stock_type' => 'regular',
            ], ['quantity' => 0]);

        // Check for negative stock (except for admin adjustments)
        if ($stock->quantity + $quantityChange < 0 && $reason !== 'adjustment') {
            throw new \Exception(
                "Cannot reduce stock below 0. Current: {$stock->quantity}, Change: {$quantityChange}"
            );
        }

        // Update stock quantity
        $stock->increment('quantity', $quantityChange);

        // Write audit trail
        return StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $productId,
            'quantity_change' => $quantityChange,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Get current stock quantity for a product in a warehouse
     */
    public function getQuantity(Warehouse $warehouse, int $productId, string $stockType = 'regular'): int
    {
        return Stock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->where('stock_type', $stockType)
            ->value('quantity') ?? 0;
    }

    /**
     * Check if sufficient stock exists before sale
     */
    public function hasSufficientStock(Warehouse $warehouse, int $productId, int $requestedQty): bool
    {
        return $this->getQuantity($warehouse, $productId, 'regular') >= $requestedQty;
    }

    /**
     * Get stock for returned/damaged items (can't be resold)
     */
    public function getReturnedStock(Warehouse $warehouse, int $productId): int
    {
        return $this->getQuantity($warehouse, $productId, 'returned_damaged');
    }

    /**
     * Move stock from returned_damaged back to regular (after inspection)
     */
    public function promoteReturnedStock(
        Warehouse $warehouse,
        int $productId,
        int $quantity,
        ?int $userId = null
    ): void {
        DB::transaction(function () use ($warehouse, $productId, $quantity, $userId) {
            // Reduce returned_damaged
            $returnedStock = Stock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->where('stock_type', 'returned_damaged')
                ->first();

            if (!$returnedStock || $returnedStock->quantity < $quantity) {
                throw new \Exception('Insufficient returned stock to promote');
            }

            $returnedStock->decrement('quantity', $quantity);

            // Increase regular stock
            $this->recordMovement(
                $warehouse,
                $productId,
                $quantity,
                'adjustment',
                $userId,
                'returned_stock_promotion',
                null
            );
        });
    }
}
