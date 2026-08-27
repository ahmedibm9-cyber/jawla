<?php

namespace App\Services\Contracts;

use App\Models\VanTransfer;

interface VanTransferService
{
    /** @param array<int, array{product_id: int, quantity: float, batch_id?: int|null}> $items */
    public function create(int $companyId, int $fromUserId, int $toUserId, array $items, ?int $inTransitWarehouseId = null): VanTransfer;

    public function approve(int $transferId, int $userId): VanTransfer;

    public function ship(int $transferId, int $fromWarehouseId, int $userId): VanTransfer;

    /** @param array<int, float>|null $itemQuantities */
    public function receive(int $transferId, int $userId, ?array $itemQuantities = null): VanTransfer;

    public function reject(int $transferId, int $userId): VanTransfer;

    public function cancel(int $transferId, int $userId): VanTransfer;
}
