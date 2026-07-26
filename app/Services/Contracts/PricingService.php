<?php

namespace App\Services\Contracts;

use App\Models\PriceQuotation;
use App\Models\ProductPrice;
use App\Models\User;
use App\Support\PriceRange;
use Carbon\CarbonInterface;

interface PricingService
{
    public function priceForRep(int $productId, int $repId, string $unitPrice): bool;

    public function rangeForRep(int $productId, int $repId): PriceRange;

    public function rangeForQuotation(PriceQuotation $quotation): PriceRange;

    public function effectivePrice(int $companyId, int $customerId, int $productId, string $quantity, ?CarbonInterface $at = null): string;

    public function createCustomerOverride(User $manager, int $companyId, int $customerId, int $productId, string $price, CarbonInterface $validFrom, CarbonInterface $validUntil, string $reason): ProductPrice;
}
