<?php

namespace App\Services\Contracts;

class LineItemInput
{
    public function __construct(
        public readonly string $qty,
        public readonly string $unitPrice,
        public readonly bool $vatApplicable,
    ) {}
}

class LineItemResult
{
    public function __construct(
        public readonly string $lineTotal,
        public readonly string $vatAmount,
        public readonly bool $vatApplicable,
    ) {}
}

class InvoiceCalculation
{
    public function __construct(
        public readonly string $subtotal,
        public readonly string $vatAmount,
        public readonly string $total,
        public readonly array $lines,
    ) {}
}

interface InvoiceCalculationService
{
    public function calculate(array $lines, string $vatPercent): InvoiceCalculation;
}
