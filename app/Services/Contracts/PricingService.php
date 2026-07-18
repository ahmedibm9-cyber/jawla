<?php

namespace App\Services\Contracts;

use App\Models\PriceQuotation;
use App\Support\PriceRange;

interface PricingService
{
    public function priceForRep(int $productId, int $repId, string $unitPrice): bool;

    public function rangeForRep(int $productId, int $repId): PriceRange;

    public function rangeForQuotation(PriceQuotation $quotation): PriceRange;
}
