<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Support\Collection;

/**
 * Batch / expiry helpers: FEFO (First-Expired-First-Out) selection for picking
 * stock, and expiry-window queries for alerting. Read-only — batch mutations
 * go through the resource/import flows.
 */
class BatchService
{
    /**
     * The batch a picker should consume first for a product (earliest expiry,
     * active, not expired). Null when the product is not batch-tracked.
     */
    public function fefoBatch(int $productId): ?Batch
    {
        return Batch::query()->fefoForProduct($productId)->first();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Batch> */
    public function expiringSoon(int $companyId, int $days = 30): Collection
    {
        return Batch::query()
            ->expiringWithin($days)
            ->whereHas('product', fn ($q) => $q->where('company_id', $companyId))
            ->with('product')
            ->orderBy('expiry_date')
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Batch> */
    public function expired(int $companyId): Collection
    {
        return Batch::query()
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->whereHas('product', fn ($q) => $q->where('company_id', $companyId))
            ->with('product')
            ->get();
    }
}
