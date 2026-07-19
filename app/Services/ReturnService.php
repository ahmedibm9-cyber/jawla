<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\ReturnItem;
use App\Models\ReturnRecord;
use App\Models\Warehouse;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\StockService;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function create(int $companyId, int $userId, int $customerId, array $items, ?int $againstInvoiceId = null, ?int $visitId = null, string $reason = ''): ReturnRecord
    {
        return DB::transaction(function () use ($companyId, $userId, $customerId, $items, $againstInvoiceId, $visitId, $reason): ReturnRecord {
            $return = ReturnRecord::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'user_id' => $userId,
                'visit_id' => $visitId,
                'against_invoice_id' => $againstInvoiceId,
                'return_number' => app(DocumentNumberService::class)->generate('sales_return', $companyId),
                'total' => 0,
                'reason' => $reason,
                'status' => 'submitted',
                'returned_at' => now(),
                'posting_date' => today(),
            ]);

            $vanWarehouse = Warehouse::where('user_id', $userId)
                ->where('type', 'van')->first();
            $total = 0;
            foreach ($items as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
                $total += $lineTotal;

                if ($vanWarehouse) {
                    $this->stock->increment(
                        $vanWarehouse->id,
                        $item['product_id'],
                        $item['batch_id'] ?? null,
                        (float) $item['quantity'],
                        StockReason::Return,
                        $return,
                        $userId,
                    );
                }
            }

            $return->update(['total' => $total]);

            $return->customer->decrement('balance', $total);

            return $return->fresh(['items']);
        });
    }

    public function cancel(ReturnRecord $return, int $userId, string $reason): ReturnRecord
    {
        return DB::transaction(function () use ($return, $userId): ReturnRecord {
            $return->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            $vanWarehouse = Warehouse::where('user_id', $return->user_id)
                ->where('type', 'van')->first();
            foreach ($return->items as $item) {
                if ($vanWarehouse) {
                    $this->stock->decrement(
                        $vanWarehouse->id,
                        $item->product_id,
                        $item->batch_id,
                        (float) $item->quantity,
                        StockReason::Adjustment,
                        $return,
                        $userId,
                    );
                }
            }

            $return->customer->increment('balance', (float) $return->total);

            return $return;
        });
    }
}
