<?php

namespace App\Services\Contracts;

use App\Models\PriceQuotation;
use App\Models\ProformaInvoice;

interface ProformaService
{
    public function createFromQuotation(PriceQuotation $quotation, array $data): ProformaInvoice;
}
