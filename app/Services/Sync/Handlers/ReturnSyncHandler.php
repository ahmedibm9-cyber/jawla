<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\ReturnService;

class ReturnSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly ReturnService $returns) {}

    public function type(): string
    {
        return 'return';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
            'against_invoice_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.condition' => ['required', 'in:sellable,damaged'],
        ]);
        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $return = $this->returns->create(
            companyId: $rep->activeCompanyId(),
            userId: $rep->id,
            customerId: (int) $data['customer_id'],
            items: array_map(fn (array $item) => [
                'invoice_item_id' => (int) $item['invoice_item_id'],
                'quantity' => (float) $item['quantity'],
                'condition' => $item['condition'],
            ], $data['items']),
            againstInvoiceId: (int) $data['against_invoice_id'],
            reason: $data['reason'],
        );

        return ['return_id' => $return->id, 'return_number' => $return->return_number];
    }
}
