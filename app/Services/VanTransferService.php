<?php

namespace App\Services;

use App\Enums\VanTransferStatus;
use App\Exceptions\Domain\DomainException;
use App\Models\Product;
use App\Models\User;
use App\Models\VanTransfer;
use App\Models\VanTransferItem;
use App\Models\Warehouse;
use App\Services\Contracts\StockService as StockServiceContract;
use App\Services\Contracts\VanTransferService as VanTransferServiceContract;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;

class VanTransferService implements VanTransferServiceContract
{
    public function __construct(
        private readonly StockServiceContract $stock,
    ) {}

    public function create(int $companyId, int $fromUserId, int $toUserId, array $items, ?int $inTransitWarehouseId = null): VanTransfer
    {
        abort_if($fromUserId === $toUserId, 422, 'Cannot transfer stock to yourself.');

        app(ActiveCompanyContext::class)->assertMatches($companyId);

        return DB::transaction(function () use ($companyId, $fromUserId, $toUserId, $items, $inTransitWarehouseId): VanTransfer {
            $participants = User::withoutGlobalScopes()
                ->whereIn('id', [$fromUserId, $toUserId])
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->get();
            if ($participants->count() !== count(array_unique([$fromUserId, $toUserId]))) {
                throw new DomainException('Transfer participants must belong to the same company.');
            }

            $productIds = array_values(array_unique(array_column($items, 'product_id')));
            if (Product::where('company_id', $companyId)->whereIn('id', $productIds)->count() !== count($productIds)) {
                throw new DomainException('Transfer products must belong to the same company.');
            }

            if ($inTransitWarehouseId !== null && ! Warehouse::whereKey($inTransitWarehouseId)
                ->where('company_id', $companyId)->lockForUpdate()->exists()) {
                throw new DomainException('In-transit warehouse does not belong to this company.');
            }

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

    public function approve(int $transferId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $userId): VanTransfer {
            $transfer = VanTransfer::whereKey($transferId)->lockForUpdate()->firstOrFail();

            throw_if($transfer->status !== VanTransferStatus::Pending, new \RuntimeException('Only pending transfers can be approved.'));
            throw_if($transfer->to_user_id !== $userId, new DomainException('Only the destination representative can approve this transfer.'));

            $transfer->update([
                'status' => VanTransferStatus::Accepted,
                'accepted_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    public function ship(int $transferId, int $fromWarehouseId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $fromWarehouseId, $userId): VanTransfer {
            $transfer = VanTransfer::with('items')->whereKey($transferId)->lockForUpdate()->firstOrFail();

            throw_if($transfer->status !== VanTransferStatus::Accepted, new \RuntimeException('Only accepted transfers can be shipped.'));
            throw_if($transfer->from_user_id !== $userId, new DomainException('Only the source representative can ship this transfer.'));

            Warehouse::whereKey($fromWarehouseId)
                ->where('company_id', $transfer->company_id)
                ->lockForUpdate()
                ->firstOrFail();

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

    public function receive(int $transferId, int $userId, ?array $itemQuantities = null): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $userId, $itemQuantities): VanTransfer {
            $transfer = VanTransfer::with('items')->whereKey($transferId)->lockForUpdate()->firstOrFail();

            throw_if($transfer->status !== VanTransferStatus::Shipped, new \RuntimeException('Only shipped transfers can be received.'));
            throw_if($transfer->to_user_id !== $userId, new DomainException('Only the destination representative can receive this transfer.'));

            $inTransitId = $transfer->in_transit_warehouse_id;
            throw_if(! $inTransitId, new \RuntimeException('In-transit warehouse is required.'));

            $vanWarehouse = Warehouse::where('user_id', $transfer->to_user_id)
                ->where('company_id', $transfer->company_id)
                ->where('type', 'van')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            throw_if(! $vanWarehouse, new \RuntimeException('Destination van warehouse not found for user.'));

            foreach ($transfer->items as $item) {
                $ordered = (float) $item->quantity;
                $received = $itemQuantities[$item->id] ?? $ordered;
                if ($received > $ordered) {
                    throw new DomainException(
                        app()->getLocale() === 'ar'
                            ? 'الكمية المستلمة لا يمكن أن تتجاوز الكمية المطلوبة'
                            : 'Received quantity cannot exceed ordered quantity'
                    );
                }
                $exception = max(0, $ordered - $received);

                $item->update([
                    'received_quantity' => $received,
                    'exception_quantity' => $exception > 0 ? $exception : null,
                    'exception_reason' => $exception > 0 ? 'shortage' : null,
                    'exceptioned_at' => $exception > 0 ? now() : null,
                ]);

                if ($received > 0) {
                    $this->stock->transfer(
                        $inTransitId,
                        $vanWarehouse->id,
                        $item->product_id,
                        $item->batch_id,
                        $received,
                        $transfer,
                        $userId,
                    );
                }
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
            $transfer = VanTransfer::with('items')->whereKey($transferId)->lockForUpdate()->firstOrFail();

            throw_if(! in_array($transfer->status, [VanTransferStatus::Pending, VanTransferStatus::Accepted, VanTransferStatus::Shipped]), new \RuntimeException('Only pending, accepted, or shipped transfers can be rejected.'));
            throw_if($transfer->to_user_id !== $userId, new DomainException('Only the destination representative can reject this transfer.'));

            if ($transfer->status === VanTransferStatus::Shipped) {
                $inTransitId = $transfer->in_transit_warehouse_id;
                throw_if(! $inTransitId, new \RuntimeException('In-transit warehouse is required.'));

                $fromWarehouse = Warehouse::where('user_id', $transfer->from_user_id)
                    ->where('company_id', $transfer->company_id)
                    ->where('type', 'van')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                throw_if(! $fromWarehouse, new \RuntimeException('Source van warehouse not found for user.'));

                foreach ($transfer->items as $item) {
                    $this->stock->transfer(
                        $inTransitId,
                        $fromWarehouse->id,
                        $item->product_id,
                        $item->batch_id,
                        (float) $item->quantity,
                        $transfer,
                        $userId,
                    );
                }
            }

            $transfer->update(['status' => VanTransferStatus::Rejected]);

            return $transfer->fresh();
        });
    }

    public function cancel(int $transferId, int $userId): VanTransfer
    {
        return DB::transaction(function () use ($transferId, $userId): VanTransfer {
            $transfer = VanTransfer::whereKey($transferId)->lockForUpdate()->firstOrFail();

            throw_if($transfer->status !== VanTransferStatus::Pending, new \RuntimeException('Only pending transfers can be cancelled.'));
            throw_if($transfer->from_user_id !== $userId, new DomainException('Only the source representative can cancel this transfer.'));

            $transfer->update(['status' => VanTransferStatus::Cancelled]);

            return $transfer->fresh();
        });
    }
}
