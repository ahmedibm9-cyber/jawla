<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\SalesOrderService;

class SalesOrderSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly SalesOrderService $orders) {}

    public function type(): string
    {
        return 'sales_order';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $validated = $this->validated($payload, [
            'customer_id' => ['required', 'integer'],
            'requested_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $this->assertCustomerInCompany($rep, (int) $validated['customer_id']);

        $order = $this->orders->createAndSubmit(
            $rep,
            (int) $validated['customer_id'],
            $validated['items'],
            [
                'requested_delivery_date' => $validated['requested_delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return ['sales_order_id' => $order->id, 'order_number' => $order->order_number];
    }
}
