<?php

namespace App\Services\Contracts;

use App\Models\OutOfStockRequest;
use App\Models\User;

interface OutOfStockService
{
    /**
     * Flag a product as out of stock and broadcast a critical alarm to
     * Finance, Manager and Executive in the rep's company. Idempotent: a
     * second flag for the same rep+product while a request is still open
     * returns the existing request and raises no second alarm.
     */
    public function raise(User $rep, int $productId, float $quantity, ?int $customerId = null, ?string $notes = null): OutOfStockRequest;

    /**
     * Mark an open request as fulfilled. Idempotent: resolving a request
     * that is no longer open is a no-op.
     */
    public function resolve(OutOfStockRequest $request, int $userId): OutOfStockRequest;
}
