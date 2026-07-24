<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\PaymentService;

/**
 * Offline payment collection → PaymentService::collect (cash box + customer
 * balance + invoice allocation), applied exactly once on sync.
 */
class PaymentSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly PaymentService $payments) {}

    public function type(): string
    {
        return 'payment';
    }

    public function handle(User $rep, array $payload): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'in:cash,cheque,transfer,other'],
            'invoice_id' => ['nullable', 'integer'],
            'visit_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $payment = $this->payments->collect(
            companyId: $rep->company_id,
            userId: $rep->id,
            customerId: (int) $data['customer_id'],
            amount: (float) $data['amount'],
            method: $data['method'],
            invoiceId: isset($data['invoice_id']) ? (int) $data['invoice_id'] : null,
            visitId: isset($data['visit_id']) ? (int) $data['visit_id'] : null,
            notes: $data['notes'] ?? null,
        );

        return ['payment_id' => $payment->id];
    }
}
