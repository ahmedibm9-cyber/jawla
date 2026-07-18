<?php

namespace App\Services;

use App\Models\PriceQuotation;
use App\Models\Product;
use App\Models\User;
use App\Services\Contracts\PricingService as PricingServiceContract;
use App\Support\Money;
use App\Support\PriceRange;

class PricingService implements PricingServiceContract
{
    public function priceForRep(int $productId, int $repId, string $unitPrice): bool
    {
        $range = $this->rangeForRep($productId, $repId);

        return $range->contains(new Money($unitPrice));
    }

    public function rangeForRep(int $productId, int $repId): PriceRange
    {
        $product = Product::find($productId);

        if (! $product) {
            return new PriceRange(Money::zero(), Money::zero(), Money::zero());
        }

        $base = new Money((string) $product->price);

        $rep = User::with('roles', 'company')->find($repId);
        $isRep = $rep && $rep->hasRole('rep');

        if (! $isRep) {
            return new PriceRange($base, Money::zero(), Money::zero());
        }

        $discount = $rep->company?->rep_discount_percent ?? 10;
        $minus = $base->percent((string) $discount);

        return new PriceRange($base, Money::zero(), $minus);
    }

    public function rangeForQuotation(PriceQuotation $quotation): PriceRange
    {
        $base = new Money((string) $quotation->base_price);
        $plus = new Money((string) $quotation->rep_plus);
        $minus = new Money((string) $quotation->rep_minus);

        return new PriceRange($base, $plus, $minus);
    }
}
