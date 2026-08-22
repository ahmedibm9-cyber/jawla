<?php

namespace App\Services\Contracts;

interface InvoiceCalculationService
{
    public function calculate(array $lines, string $vatPercent): InvoiceCalculation;
}
