<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\Product;
use App\Models\StockCountItem;
use App\Models\StockCountSession;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\StockService;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;

class StockCountService
{
    public function __construct(private readonly StockService $stock) {}

    public function open(Warehouse $warehouse, User $keeper, array $productIds): StockCountSession
    {
        app(ActiveCompanyContext::class)->assertMatches((int) $warehouse->company_id);
        if (! $keeper->can('stock.adjust') || ! $keeper->hasCompanyAccess((int) $warehouse->company_id)) {
            throw new DomainException('Only an assigned warehouse keeper may open a stock count.');
        }
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        if ($ids === [] || Product::where('company_id', $warehouse->company_id)->whereIn('id', $ids)->count() !== count($ids)) {
            throw new DomainException('Stock-count products must belong to the warehouse company.');
        }

        return DB::transaction(function () use ($warehouse, $keeper, $ids): StockCountSession {
            $session = StockCountSession::create([
                'company_id' => $warehouse->company_id,
                'warehouse_id' => $warehouse->id,
                'opened_by' => $keeper->id,
                'status' => 'counting',
            ]);
            foreach ($ids as $productId) {
                StockCountItem::create([
                    'stock_count_session_id' => $session->id,
                    'product_id' => $productId,
                    'expected_quantity' => number_format($this->stock->balance($warehouse->id, $productId), 3, '.', ''),
                ]);
            }

            return $session->fresh('items');
        });
    }

    public function record(StockCountSession $session, int $itemId, string $physicalQuantity, User $keeper): StockCountItem
    {
        if ((int) $session->opened_by !== (int) $keeper->id || $session->status !== 'counting') {
            throw new DomainException('Only the opening warehouse keeper may record this active count.');
        }
        $quantity = number_format((float) $physicalQuantity, 3, '.', '');
        if (bccomp($quantity, '0.000', 3) < 0) {
            throw new DomainException('Physical quantity cannot be negative.');
        }
        $item = $session->items()->whereKey($itemId)->firstOrFail();
        $item->update([
            'physical_quantity' => $quantity,
            'variance' => bcsub($quantity, (string) $item->expected_quantity, 3),
        ]);

        return $item->fresh();
    }

    public function submit(StockCountSession $session, User $keeper, string $reason): StockCountSession
    {
        if ((int) $session->opened_by !== (int) $keeper->id || trim($reason) === '') {
            throw new DomainException('The opening warehouse keeper and a variance reason are required.');
        }
        if ($session->items()->whereNull('physical_quantity')->exists()) {
            throw new DomainException('Every stock-count line requires a physical quantity.');
        }
        $session->update(['status' => 'pending_approval', 'reason' => trim($reason), 'submitted_at' => now()]);

        return $session->fresh();
    }

    public function approveAndApply(StockCountSession $session, User $manager): StockCountSession
    {
        if (! $manager->can('update:stock') || ! $manager->hasCompanyAccess((int) $session->company_id)) {
            throw new DomainException('Only an assigned sales manager may approve a stock count.');
        }

        return DB::transaction(function () use ($session, $manager): StockCountSession {
            $session = StockCountSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status === 'applied') {
                return $session;
            }
            if ($session->status !== 'pending_approval') {
                throw new DomainException('Only a submitted stock count may be approved.');
            }
            foreach ($session->items()->lockForUpdate()->get() as $item) {
                $this->stock->reconcile(
                    $session->warehouse_id,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->physical_quantity,
                    $session->reason,
                    $manager->id,
                    (float) $item->expected_quantity,
                    $session,
                );
            }
            $session->update([
                'approved_by' => $manager->id,
                'status' => 'applied',
                'approved_at' => now(),
                'applied_at' => now(),
            ]);

            return $session->fresh('items');
        });
    }
}
