<?php

namespace App\Services\Contracts;

use App\Models\Payment;

interface PaymentService
{
    public function collect(int $companyId, int $userId, int $customerId, float $amount, string $method, ?int $invoiceId = null, ?int $visitId = null, ?string $notes = null): Payment;

    public function cancel(Payment $payment, int $userId, string $reason): Payment;
}
