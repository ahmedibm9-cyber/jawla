<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\OutOfStockRequest;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OutOfStockResolved;
use App\Services\Contracts\AlarmService;
use App\Services\Contracts\OutOfStockService as OutOfStockServiceContract;
use Illuminate\Support\Facades\DB;

class OutOfStockService implements OutOfStockServiceContract
{
    public function __construct(private readonly AlarmService $alarms) {}

    public function raise(User $rep, int $productId, float $quantity, ?int $customerId = null, ?string $notes = null): OutOfStockRequest
    {
        $product = Product::withoutGlobalScopes()
            ->where('company_id', $rep->company_id)
            ->findOrFail($productId);

        return DB::transaction(function () use ($rep, $product, $quantity, $customerId, $notes): OutOfStockRequest {
            // Idempotency: reuse an existing open request for this rep+product
            // so retries and double-taps never fan out a second alarm.
            $existing = OutOfStockRequest::withoutGlobalScopes()
                ->where('company_id', $rep->company_id)
                ->where('user_id', $rep->id)
                ->where('product_id', $product->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $request = OutOfStockRequest::create([
                'company_id' => $rep->company_id,
                'user_id' => $rep->id,
                'customer_id' => $customerId,
                'product_id' => $product->id,
                'quantity_requested' => $quantity,
                'notes' => $notes,
                'status' => 'open',
            ]);

            $productName = $product->name_ar ?: $product->name_en;

            $this->alarms->raise(
                'out_of_stock_request',
                $request,
                "نفاد مخزون / Out of stock: {$productName}",
                "المندوب {$rep->name} / Rep {$rep->name} — {$product->sku} (".rtrim(rtrim(number_format($quantity, 3), '0'), '.').')',
                'critical',
            );

            return $request;
        });
    }

    public function resolve(OutOfStockRequest $request, int $userId): OutOfStockRequest
    {
        return DB::transaction(function () use ($request, $userId): OutOfStockRequest {
            if ($request->status !== 'open') {
                return $request;
            }

            $request->update(['status' => 'fulfilled']);

            Activity::log('out_of_stock_fulfilled', $request, sprintf(
                'Out-of-stock request #%d fulfilled by user #%d',
                $request->id,
                $userId,
            ));

            // afterCommit: a rolled-back fulfilment must never notify the rep.
            DB::afterCommit(function () use ($request): void {
                $request->user?->notify(new OutOfStockResolved($request));
            });

            return $request;
        });
    }
}
