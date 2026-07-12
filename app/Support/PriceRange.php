<?php

declare(strict_types=1);

namespace App\Support;

final readonly class PriceRange
{
    public function __construct(
        public Money $base,
        public Money $plus,
        public Money $minus,
    ) {
    }

    public function lowerBound(): Money
    {
        return $this->base->sub($this->minus);
    }

    public function upperBound(): Money
    {
        return $this->base->add($this->plus);
    }

    public function contains(Money $price): bool
    {
        return bccomp($price->toDecimal(), $this->lowerBound()->toDecimal(), 2) >= 0
            && bccomp($price->toDecimal(), $this->upperBound()->toDecimal(), 2) <= 0;
    }
}