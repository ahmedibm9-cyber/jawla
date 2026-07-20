<?php

namespace App\Services\Eta;

use App\Services\Eta\Contracts\EtaClient;

/**
 * Default binding when ETA is not configured. It never contacts ETA and always
 * reports a not-configured rejection, so a misconfiguration can never silently
 * "succeed". Swapped for a real HTTP client once credentials + certificate are
 * provisioned.
 */
class NullEtaClient implements EtaClient
{
    public function submit(array $document): EtaResult
    {
        return EtaResult::rejected(['ETA client is not configured.']);
    }
}
