<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\ReturnRequestService;

class ReturnRequestSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly ReturnRequestService $returns) {}

    public function type(): string
    {
        return 'return_request';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'against_invoice_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.condition' => ['required', 'in:sellable,damaged'],
        ]);
        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $request = $this->returns->submit(
            $rep,
            (int) $data['customer_id'],
            (int) $data['against_invoice_id'],
            array_map(fn (array $item): array => [
                'invoice_item_id' => (int) $item['invoice_item_id'],
                'quantity' => (float) $item['quantity'],
                'condition' => $item['condition'],
            ], $data['items']),
            $data['reason'],
        );

        return ['return_request_id' => $request->id, 'request_number' => $request->request_number];
    }
}
