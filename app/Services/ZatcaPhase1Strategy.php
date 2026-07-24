<?php

namespace App\Services;

class ZatcaPhase1Strategy extends ZatcaQrBase
{
    protected function phaseLabel(): string
    {
        return 'ZATCA Phase 1 QR';
    }
}
