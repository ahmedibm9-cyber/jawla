<?php

namespace App\Services\Eta\Exceptions;

class EtaNotConfiguredException extends \RuntimeException
{
    public static function make(): self
    {
        return new self(
            'ETA e-invoicing is not configured. Set ETA_ENABLED=true and provide '
            .'ETA_TAXPAYER_RIN plus OAuth credentials and a signing certificate '
            .'before submitting real invoices (go-live gate).'
        );
    }
}
