<?php

namespace App\Data;

final readonly class ReturnStockDestination
{
    public function __construct(
        public int $sellableWarehouseId,
        public ?int $quarantineWarehouseId,
        public int $stockActorId,
    ) {}
}
