<?php

namespace App\Services;

use App\Services\Contracts\InvoiceCalculation;
use App\Services\Contracts\InvoiceCalculationService as Contract;
use App\Services\Contracts\LineItemInput;
use App\Services\Contracts\LineItemResult;

class InvoiceCalculationService implements Contract
{
    /** @param LineItemInput[] $lines */
    public function calculate(array $lines, string $vatPercent): InvoiceCalculation
    {
        $results = [];
        $subtotal = '0.00';

        foreach ($lines as $input) {
            $lineTotal = bcmul($input->qty, $input->unitPrice, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);

            if ($input->vatApplicable) {
                $vatAmount = bcmul($lineTotal, bcdiv($vatPercent, '100', 4), 2);
                $results[] = new LineItemResult($lineTotal, $vatAmount, true);
            } else {
                $results[] = new LineItemResult($lineTotal, '0.00', false);
            }
        }

        $vatAmount = array_reduce($results, fn ($sum, $r) => bcadd($sum, $r->vatAmount, 2), '0.00');
        $total = bcadd($subtotal, $vatAmount, 2);

        return new InvoiceCalculation($subtotal, $vatAmount, $total, $results);
    }
}
