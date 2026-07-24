<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Enums\StockReason;
use App\Models\Customer;
use App\Models\Product;
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
            $vanWarehouse = Warehouse::where('user_id', $userId)
                ->where('company_id', $companyId)
                ->where('type', 'van')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            if (! $vanWarehouse) {
                throw new DomainException(
                    app()->getLocale() === 'ar'
                        ? 'لا يمكن تسجيل مرتجع بدون مخزن سيارة نشط للمندوب'
                        : 'A return requires an active van warehouse for the seller.'
                );
            }
            $customer = Customer::whereKey($customerId)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();
            if (! $customer) {
                throw new DomainException($this->companyMessage('customer'));
            }

            $productIds = array_values(array_unique(array_column($items, 'product_id')));
            if (Product::where('company_id', $companyId)->whereIn('id', $productIds)->count() !== count($productIds)) {
                throw new DomainException($this->companyMessage('product'));
            }

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

            $return->update(['total' => $total]);

            // Guard: prevent negative customer balance
            if ($total > (float) $customer->balance) {
                throw new DomainException(
                    app()->getLocale() === 'ar'
                        ? 'قيمة المرتجع تتجاوز رصيد العميل'
                        : 'Return value exceeds customer balance'
                );
            }

            $customer->decrement('balance', $total);

            return $return->fresh(['items']);
        });
    }

    public function cancel(ReturnRecord $return, int $userId, string $reason): ReturnRecord
    {
        return DB::transaction(function () use ($return, $userId): ReturnRecord {
            $return = ReturnRecord::whereKey($return->id)->lockForUpdate()->firstOrFail();
            if ($return->cancelled_at !== null) {
                return $return;
            }
            $return->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            $vanWarehouse = Warehouse::where('user_id', $return->user_id)
                ->where('company_id', $return->company_id)
                ->where('type', 'van')->where('is_active', true)->lockForUpdate()->firstOrFail();
            foreach ($return->items as $item) {
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

            Customer::whereKey($return->customer_id)
                ->where('company_id', $return->company_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('balance', (float) $return->total);

            return $return;
        });
    }

    private function companyMessage(string $resource): string
    {
        $english = ucfirst($resource).' does not belong to this company.';
        $arabic = match ($resource) {
            'customer' => 'العميل لا يتبع هذه الشركة.',
            'product' => 'المنتج لا يتبع هذه الشركة.',
            default => 'السجل لا يتبع هذه الشركة.',
        };

        return app()->getLocale() === 'ar' ? $arabic : $english;
    }
}
