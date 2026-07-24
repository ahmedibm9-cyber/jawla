<?php

namespace App\Services;

class ZatcaPhase2Strategy extends ZatcaQrBase
{
    protected function phaseLabel(): string
    {
        return 'ZATCA Phase 2 QR';
    }
}
