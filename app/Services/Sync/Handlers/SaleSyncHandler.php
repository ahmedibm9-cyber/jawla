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

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'visit_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            // Older clients queued their quoted price; current clients omit it.
            // InvoiceService always resolves the authoritative price server-side
            // and only uses a supplied price to detect a stale quotation.
            'items.*.unit_price' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $this->assertCustomerInCompany($rep, (int) $data['customer_id']);

        $invoice = $this->invoices->create([
            'company_id' => $rep->activeCompanyId(),
            'customer_id' => (int) $data['customer_id'],
            'visit_id' => $data['visit_id'] ?? null,
            'items' => array_map(function (array $item): array {
                $normalized = [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (float) $item['quantity'],
                ];

                if (array_key_exists('unit_price', $item)) {
                    $normalized['unit_price'] = (float) $item['unit_price'];
                }

                return $normalized;
            }, $data['items']),
        ]);

        return ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number];
    }
}
