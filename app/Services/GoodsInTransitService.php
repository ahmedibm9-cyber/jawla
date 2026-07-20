<?php

namespace App\Services;

use App\Enums\StockReason;
use App\Models\Activity;
use App\Models\GoodsInTransit;
use App\Services\Contracts\StockService as StockServiceContract;
use Illuminate\Support\Facades\DB;

/**
 * Goods-in-transit lifecycle and landed-cost allocation (Amendment R3).
 *
 * Landed cost = the shipment's shipping/customs/clearance/freight plus any
 * itemised landed_costs rows, allocated across items in proportion to their
 * line value so each product's true unit cost (COGS) reflects duties/freight.
 * `receive()` moves the shipment into a warehouse through StockService, so
 * every unit lands with a matching stock movement.
 */
class GoodsInTransitService
{
    public function __construct(
        private readonly StockServiceContract $stock,
    ) {}

    public function totalLandedCost(GoodsInTransit $git): float
    {
        $base = (float) $git->shipping_cost + (float) $git->customs_cost
            + (float) $git->clearance_cost + (float) $git->freight_cost;

        return round($base + (float) $git->landedCosts()->sum('amount'), 2);
    }

    /**
     * Allocate the total landed cost across items by line value.
     *
     * @return array<int, array{allocated: float, landed_unit_cost: float}> keyed by item id
     */
    public function allocate(GoodsInTransit $git): array
    {
        $git->loadMissing('items');
        $total = $this->totalLandedCost($git);

        $lineValues = $git->items->mapWithKeys(fn ($i) => [$i->id => (float) $i->quantity * (float) $i->unit_price]);
        $sumValues = $lineValues->sum();

        $allocation = [];
        foreach ($git->items as $item) {
            $share = $sumValues > 0 ? ($lineValues[$item->id] / $sumValues) : 0.0;
            $allocated = round($total * $share, 2);
            $qty = (float) $item->quantity;
            $allocation[$item->id] = [
                'allocated' => $allocated,
                'landed_unit_cost' => round((float) $item->unit_price + ($qty > 0 ? $allocated / $qty : 0), 2),
            ];
        }

        return $allocation;
    }

    /**
     * Receive the shipment into a warehouse: every item's quantity is added to
     * stock (reason transit_in) and the shipment is marked received.
     */
    public function receive(GoodsInTransit $git, int $warehouseId, int $userId): GoodsInTransit
    {
        return DB::transaction(function () use ($git, $warehouseId, $userId): GoodsInTransit {
            $fresh = GoodsInTransit::whereKey($git->getKey())->with('items')->lockForUpdate()->firstOrFail();

            throw_if(in_array($fresh->status, ['received', 'cancelled'], true), new \RuntimeException(
                "Shipment {$fresh->shipment_number} is already {$fresh->status}."));

            foreach ($fresh->items as $item) {
                $this->stock->increment(
                    $warehouseId,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    StockReason::TransitIn,
                    $fresh,
                    $userId,
                );
                $item->update(['received_quantity' => $item->quantity]);
            }

            $fresh->update(['status' => 'received']);

            Activity::log('goods_in_transit_received', $fresh,
                "Shipment {$fresh->shipment_number} received into warehouse {$warehouseId}");

            return $fresh->fresh()->load('items');
        });
    }
}
