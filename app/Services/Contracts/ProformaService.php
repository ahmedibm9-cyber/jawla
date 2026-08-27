<?php

namespace App\Services\Contracts;

use App\Models\PriceQuotation;
use App\Models\ProformaInvoice;

interface ProformaService
{
    /** @param array<string, mixed> $data */
    public function createFromQuotation(PriceQuotation $quotation, array $data): ProformaInvoice;
}
