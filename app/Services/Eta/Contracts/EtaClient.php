<?php

namespace App\Services\Eta\Contracts;

use App\Services\Eta\EtaResult;

/**
 * Transport for submitting a built ETA document to the tax authority.
 * The real implementation (HTTP + OAuth + signed payload) requires the
 * taxpayer's ETA credentials and certificate; NullEtaClient is bound until
 * those are configured.
 */
interface EtaClient
{
    /**
     * @param  array<string, mixed>  $document  ETA-shaped invoice document
     */
    public function submit(array $document): EtaResult;
}
