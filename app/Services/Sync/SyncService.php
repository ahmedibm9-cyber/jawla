<?php

namespace App\Services\Sync;

use App\Models\SyncReceipt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Applies a batch of offline-queued operations with exactly-once semantics.
 *
 * For each operation the engine:
 *  1. returns the stored response if the (company, idempotency_key) was already
 *     applied (replay — never re-runs);
 *  2. reserves the key by inserting a receipt (the unique constraint makes this
 *     the concurrency guard — a racing duplicate is caught and reported as such);
 *  3. runs the type's handler inside a DB::transaction, then stores its response;
 *  4. on failure, releases the reservation so the client can safely retry.
 *
 * Unknown operation types are reported "unsupported" rather than failing the
 * whole batch. Handlers themselves are the only place that mutates domain data.
 */
class SyncService
{
    public function __construct(private readonly SyncHandlerRegistry $registry) {}

    /**
     * @param  array<int, array{key?: string, idempotency_key?: string, type?: string, payload?: array}>  $operations
     * @return array<int, array{key: ?string, status: string, result?: array, error?: string}>
     */
    public function process(User $rep, array $operations): array
    {
        $results = [];

        foreach ($operations as $op) {
            $results[] = $this->processOne($rep, $op);
        }

        return $results;
    }

    /**
     * @param  array{key?: string, idempotency_key?: string, type?: string, payload?: array}  $op
     * @return array{key: ?string, status: string, result?: array, error?: string}
     */
    private function processOne(User $rep, array $op): array
    {
        $key = $op['key'] ?? $op['idempotency_key'] ?? null;
        $type = $op['type'] ?? null;
        $payload = $op['payload'] ?? [];

        if (! is_string($key) || $key === '' || ! is_string($type) || $type === '') {
            return ['key' => $key, 'status' => 'invalid', 'error' => 'Missing idempotency key or type.'];
        }

        $existing = $this->findReceipt($rep, $key);
        if ($existing !== null) {
            return ['key' => $key, 'status' => 'duplicate', 'result' => $existing->response ?? []];
        }

        if (! $this->registry->has($type)) {
            return ['key' => $key, 'status' => 'unsupported'];
        }

        // Reserve the key. A racing request with the same key hits the unique
        // constraint and is reported as a duplicate instead of double-applying.
        try {
            $receipt = SyncReceipt::create([
                'company_id' => $rep->company_id,
                'user_id' => $rep->id,
                'idempotency_key' => $key,
                'operation_type' => $type,
                'response' => null,
            ]);
        } catch (QueryException $e) {
            $existing = $this->findReceipt($rep, $key);

            return ['key' => $key, 'status' => 'duplicate', 'result' => $existing?->response ?? []];
        }

        try {
            $result = DB::transaction(fn () => $this->registry->get($type)->handle($rep, $payload));
            $receipt->update(['response' => $result]);

            return ['key' => $key, 'status' => 'applied', 'result' => $result];
        } catch (\Throwable $e) {
            // Handler rolled back; release the reservation so a retry can re-apply.
            $receipt->delete();

            return ['key' => $key, 'status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function findReceipt(User $rep, string $key): ?SyncReceipt
    {
        return SyncReceipt::query()
            ->where('company_id', $rep->company_id)
            ->where('idempotency_key', $key)
            ->first();
    }
}
