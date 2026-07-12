<?php

namespace App\Services\Contracts;

use App\Enums\StockReason;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

interface StockService
{
    public function decrement(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId = null): StockMovement;

    public function increment(int $warehouseId, int $productId, ?int $batchId, float $qty, StockReason $reason, Model $ref, ?int $userId = null): StockMovement;

    public function transfer(int $fromWarehouseId, int $toWarehouseId, int $productId, ?int $batchId, float $qty, Model $ref, ?int $userId = null): StockMovement;

    public function balance(int $warehouseId, int $productId, ?int $batchId = null): float;

    public function reconcile(int $warehouseId, int $productId, ?int $batchId, float $countedQty, string $reason, int $userId): StockMovement;
}