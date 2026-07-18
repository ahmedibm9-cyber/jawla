<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Enums\VanTransferStatus;
use App\Models\VanTransfer;
use App\Models\VanTransferItem;
use App\Services\Contracts\StockService as StockServiceContract;
use App\Services\Contracts\VanTransferService as VanTransferServiceContract;
use Illuminate\Support\Facades\DB;

class VanTransferService implements VanTransferServiceContract
{
    public function __construct(
        private readonly StockServiceContract $stock,
    ) {}
    public function create(int $companyId, int $fromUserId, int $toUserId, array $items, ?int $inTransitWarehouseId = null): VanTransfer
    {
        return DB::transaction(function () use ($companyId, $fromUserId, $toUserId, $items, $inTransitWarehouseId): VanTransfer {
            $transfer = VanTransfer::create([
                'company_id' => $companyId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'status' => VanTransferStatus::Pending,
                'in_transit_warehouse_id' => $inTransitWarehouseId,
            ]);

            foreach ($items as $item) {
                VanTransferItem::create([
                    'van_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function ship(int $transferId, int $fromWarehouseId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $fromWarehouseId, $userId): VanTransfer {
            $transfer = VanTransfer::with('items')->findOrFail($transferId);

            throw_if($transfer->status !== VanTransferStatus::Pending, new \RuntimeException('Only pending transfers can be shipped.'));

            $inTransitId = $transfer->in_transit_warehouse_id;
            throw_if(! $inTransitId, new \RuntimeException('In-transit warehouse is required.'));

            foreach ($transfer->items as $item) {
                $this->stock->transfer(
                    $fromWarehouseId,
                    $inTransitId,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    $transfer,
                    $userId,
                );
            }

            $transfer->update([
                'status' => VanTransferStatus::Shipped,
                'shipped_at' => now(),
            ]);

            return $transfer->fresh()->load('items');
        });
    }

    public function receive(int $transferId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $userId): VanTransfer {
            $transfer = VanTransfer::with('items')->findOrFail($transferId);

            throw_if($transfer->status !== VanTransferStatus::Shipped, new \RuntimeException('Only shipped transfers can be received.'));

            $inTransitId = $transfer->in_transit_warehouse_id;
            throw_if(! $inTransitId, new \RuntimeException('In-transit warehouse is required.'));

            $vanWarehouse = \App\Models\Warehouse::where('user_id', $transfer->to_user_id)
                ->where('type', 'van')->first();

            throw_if(! $vanWarehouse, new \RuntimeException('Destination van warehouse not found for user.'));

            foreach ($transfer->items as $item) {
                $this->stock->transfer(
                    $inTransitId,
                    $vanWarehouse->id,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    $transfer,
                    $userId,
                );
            }

            $transfer->update([
                'status' => VanTransferStatus::Received,
                'received_at' => now(),
            ]);

            return $transfer->fresh()->load('items');
        });
    }

    public function reject(int $transferId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $userId): VanTransfer {
            $transfer = VanTransfer::findOrFail($transferId);

            throw_if(! in_array($transfer->status, [VanTransferStatus::Pending, VanTransferStatus::Accepted]), new \RuntimeException('Only pending or accepted transfers can be rejected.'));

            $transfer->update(['status' => VanTransferStatus::Rejected]);

            return $transfer->fresh();
        });
    }

    public function cancel(int $transferId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $userId): VanTransfer {
            $transfer = VanTransfer::findOrFail($transferId);

            throw_if($transfer->status !== VanTransferStatus::Pending, new \RuntimeException('Only pending transfers can be cancelled.'));

            $transfer->update(['status' => VanTransferStatus::Cancelled]);

            return $transfer->fresh();
        });
    }
}
