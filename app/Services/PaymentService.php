<?php

namespace App\Services;

class PaymentService implements Contracts\PaymentService
{
    public function collect(array $data)
    {
        // Implemented in Phase 9.
        return null;
    }

    public function cancel($payment, int $userId)
    {
        // Implemented in Phase 9.
        return null;
    }
}