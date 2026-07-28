<?php

namespace App\Services\Contracts;

use App\Models\VanTransfer;

interface VanTransferService
{
    public function create(int $companyId, int $fromUserId, int $toUserId, array $items, ?int $inTransitWarehouseId = null): VanTransfer;

    public function approve(int $transferId, int $userId): VanTransfer;

    public function ship(int $transferId, int $fromWarehouseId, int $userId): VanTransfer;

    public function receive(int $transferId, int $userId, ?array $itemQuantities = null): VanTransfer;

    public function reject(int $transferId, int $userId): VanTransfer;

    public function cancel(int $transferId, int $userId): VanTransfer;
}
