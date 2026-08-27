<?php

namespace App\Services\Contracts;

use App\Models\Invoice;

interface InvoiceService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Invoice;

    public function submit(Invoice $invoice): Invoice;

    public function cancel(Invoice $invoice, int $userId, string $reason): Invoice;

    public function amend(Invoice $invoice): Invoice;
}
