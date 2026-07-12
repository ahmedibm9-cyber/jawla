<?php

namespace App\Services;

use App\Services\Contracts\PricingService;
use App\Support\PriceRange;
use App\Support\Money;

class PricingService implements Contracts\PricingService
{
    public function priceForRep(int $productId, int $repId, string $unitPrice): bool
    {
        $range = $this->rangeForRep($productId, $repId);

        return $range->contains(new Money($unitPrice));
    }

    public function rangeForRep(int $productId, int $repId): PriceRange
    {
        // Implemented in Phase 6.
        return new PriceRange(Money::zero(), Money::zero(), Money::zero());
    }
}