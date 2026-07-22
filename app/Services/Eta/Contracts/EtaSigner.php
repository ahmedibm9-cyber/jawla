<?php

namespace App\Services\Eta\Contracts;

/**
 * Signs an ETA document before submission. Real signing is CAdES-BES with the
 * taxpayer's certificate (the go-live cert dependency); UnsignedEtaSigner is the
 * default until that certificate + signing mechanism are provisioned.
 */
interface EtaSigner
{
    /**
     * @param  array<string, mixed>  $document  the built, unsigned ETA document
     * @return array<string, mixed> the document with a `signatures` array attached
     */
    public function sign(array $document): array;
}
