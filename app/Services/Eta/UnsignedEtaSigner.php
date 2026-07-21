<?php

namespace App\Services\Eta;

use App\Services\Eta\Contracts\EtaSigner;

/**
 * Default signer used until a real taxpayer certificate is provisioned. It does
 * NOT sign — it returns the document unchanged. Submitting an unsigned document
 * to ETA preprod is rejected, which is the correct, honest behaviour: it can
 * never produce a false "accepted". Swap for a CAdES-BES signer (cert-backed) at
 * go-live.
 */
class UnsignedEtaSigner implements EtaSigner
{
    public function sign(array $document): array
    {
        return $document;
    }
}
