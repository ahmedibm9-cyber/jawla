<?php

namespace App\Services\Contracts;

class InvoiceCalculation
{
    public function __construct(
        public readonly string $subtotal,
        public readonly string $vatAmount,
        public readonly string $total,
        public readonly array $lines,
    ) {}
}
