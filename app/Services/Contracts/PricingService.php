<?php

namespace App\Services\Contracts;

use App\Support\PriceRange;

interface PricingService
{
    public function priceForRep(int $productId, int $repId, string $unitPrice): bool;

    public function rangeForRep(int $productId, int $repId): PriceRange;
}