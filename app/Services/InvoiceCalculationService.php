<?php

namespace App\Services;

use App\Services\Contracts\InvoiceCalculation;
use App\Services\Contracts\InvoiceCalculationService as Contract;
use App\Services\Contracts\LineItemInput;
use App\Services\Contracts\LineItemResult;

class InvoiceCalculationService implements Contract
{
    public function calculate(array $lines, float $vatPercent): InvoiceCalculation
    {
        $results = [];
        $subtotal = 0.0;

        foreach ($lines as $input) {
            $lineTotal = round($input->qty * $input->unitPrice, 2);
            $subtotal += $lineTotal;

            if ($input->vatApplicable) {
                $results[] = new LineItemResult($lineTotal, round($lineTotal * ($vatPercent / 100), 2), true);
            } else {
                $results[] = new LineItemResult($lineTotal, 0.0, false);
            }
        }

        $vatAmount = array_sum(array_map(fn ($r) => $r->vatAmount, $results));
        $total = round($subtotal + $vatAmount, 2);

        return new InvoiceCalculation($subtotal, $vatAmount, $total, $results);
    }
}
