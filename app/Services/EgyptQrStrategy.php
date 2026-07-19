<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\Contracts\QrStrategy;

class EgyptQrStrategy implements QrStrategy
{
    public function generate(object $document): string
    {
        if ($document instanceof Invoice) {
            return $document->invoice_number.'|'.number_format((float) $document->total, 2, '.', '');
        }

        if ($document instanceof ProformaInvoice) {
            return $document->proforma_number.'|'.number_format((float) $document->total, 2, '.', '');
        }

        throw new \InvalidArgumentException('Unsupported document type for Egypt QR');
    }
}
