<?php

namespace App\Services\Contracts;

class LineItemInput
{
    public function __construct(
        public readonly float $qty,
        public readonly float $unitPrice,
        public readonly bool $vatApplicable,
    ) {}
}

class LineItemResult
{
    public function __construct(
        public readonly float $lineTotal,
        public readonly float $vatAmount,
        public readonly bool $vatApplicable,
    ) {}
}

class InvoiceCalculation
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $vatAmount,
        public readonly float $total,
        public readonly array $lines,
    ) {}
}

interface InvoiceCalculationService
{
    public function calculate(array $lines, float $vatPercent): InvoiceCalculation;
}
