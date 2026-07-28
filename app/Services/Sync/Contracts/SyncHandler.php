<?php

namespace App\Services\Sync\Contracts;

use App\Models\User;

/**
 * Applies a single offline-queued operation of one type. Implementations must be
 * safe to run inside a DB::transaction and should throw on business/validation
 * failure (the sync engine rolls back and reports the operation as failed, so the
 * client can retry). The returned array is stored as the operation's response and
 * echoed back on any later replay of the same idempotency key.
 *
 * @return array<string, mixed>
 */
interface SyncHandler
{
    /** Unique operation type key for routing offline-queued payloads. */
    public function type(): string;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array;
}
