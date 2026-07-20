<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\ReturnService;

/**
 * Offline return → ReturnService::create (van stock increase + balance adjust),
 * applied exactly once on sync.
 */
class ReturnSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly ReturnService $returns) {}

    public function handle(User $rep, array $payload): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:500'],
            'against_invoice_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $return = $this->returns->create(
            companyId: $rep->company_id,
            userId: $rep->id,
            customerId: (int) $data['customer_id'],
            items: array_map(fn ($i) => [
                'product_id' => (int) $i['product_id'],
                'quantity' => (float) $i['quantity'],
                'unit_price' => (float) $i['unit_price'],
            ], $data['items']),
            againstInvoiceId: isset($data['against_invoice_id']) ? (int) $data['against_invoice_id'] : null,
            reason: $data['reason'] ?? '',
        );

        return ['return_id' => $return->id, 'return_number' => $return->return_number];
    }
}
