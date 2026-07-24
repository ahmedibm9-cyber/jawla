<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\InvoiceService;

/**
 * Offline sale → invoice. Replays through the same InvoiceService::create the
 * online Sales Flow uses (atomic invoice + items + stock decrement + movements +
 * balance). Company/user come from the rep; the sync engine guarantees the sale
 * is applied exactly once.
 */
class SaleSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function type(): string
    {
        return 'sale';
    }

    public function handle(User $rep, array $payload): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'visit_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $invoice = $this->invoices->create([
            'company_id' => $rep->company_id,
            'customer_id' => (int) $data['customer_id'],
            'visit_id' => $data['visit_id'] ?? null,
            'items' => array_map(fn ($i) => [
                'product_id' => (int) $i['product_id'],
                'quantity' => (float) $i['quantity'],
                'unit_price' => (float) $i['unit_price'],
            ], $data['items']),
        ]);

        return ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number];
    }
}
