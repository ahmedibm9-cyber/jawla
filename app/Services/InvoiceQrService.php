<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\ProformaInvoice;
use App\Services\Contracts\QrStrategy;

class InvoiceQrService
{
    public function generateForInvoice(Invoice $invoice): string
    {
        $strategy = $this->resolveStrategy($invoice->company, 'invoice');

        return $strategy->generate($invoice);
    }

    public function generateForProforma(ProformaInvoice $proforma): string
    {
        $strategy = $this->resolveStrategy($proforma->company, 'proforma');

        return $strategy->generate($proforma);
    }

    public function resolveStrategy(Company $company, string $documentType): QrStrategy
    {
        $country = $company->country ?? 'EG';

        if ($country === 'EG' && $company->eta_enabled) {
            return app(EtaQrStrategy::class);
        }

        if ($country === 'SA' && $company->zatca_enabled) {
            // ZATCA Phase 2 requires cryptographic stamp - check if CSID is available
            if (! empty($company->zatca_csid)) {
                return app(ZatcaPhase2Strategy::class);
            }

            // Fallback to Phase 1 if CSID not yet available
            return app(ZatcaPhase1Strategy::class);
        }

        // Egypt or other countries - use simple format
        return app(EgyptQrStrategy::class);
    }
}
