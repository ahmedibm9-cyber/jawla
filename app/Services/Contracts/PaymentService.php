<?php

namespace App\Services\Contracts;

use App\Models\Payment;

interface PaymentService
{
    public function collect(array $data): Payment;

    public function cancel(Payment $payment, int $userId): Payment;
}