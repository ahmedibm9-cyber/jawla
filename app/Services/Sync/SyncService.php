<?php

namespace App\Services\Sync;

use App\Models\SyncReceipt;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
     * @param  array<int, array{key?: string, idempotency_key?: string, type?: string, payload?: array, payload_hash?: string, device_id?: string}>  $operations
     * @return array<int, array{key: ?string, status: string, result?: array, error?: string, error_code?: string}>
     */
    public function process(User $rep, array $operations, int $protocolVersion = 1): array
    {
        app(ActiveCompanyContext::class)->assertMatches($rep->activeCompanyId());

        $results = [];

        foreach ($operations as $op) {
            $results[] = $this->processOne($rep, $op, $protocolVersion);
        }

        return $results;
    }

    /**
     * @param  array{key?: string, idempotency_key?: string, type?: string, payload?: array, payload_hash?: string, device_id?: string}  $op
     * @return array{key: ?string, status: string, result?: array, error?: string, error_code?: string}
     */
    private function processOne(User $rep, array $op, int $protocolVersion): array
    {
        $key = $op['key'] ?? $op['idempotency_key'] ?? null;
        $type = $op['type'] ?? null;
        $payload = $op['payload'] ?? [];
        $payloadHash = $op['payload_hash'] ?? null;
        $deviceId = $op['device_id'] ?? null;

        if (! is_string($key) || $key === '' || ! is_string($type) || $type === '') {
            return ['key' => $key, 'status' => 'invalid', 'error' => 'Missing idempotency key or type.'];
        }

        if (! $this->registry->has($type)) {
            return ['key' => $key, 'status' => 'unsupported'];
        }

        try {
            return DB::transaction(function () use ($rep, $key, $type, $payload, $protocolVersion, $payloadHash, $deviceId): array {
                // Both the receipt and domain write must commit or roll back
                // together. A receipt with no response is a legacy ambiguous
                // state and is deliberately quarantined rather than replayed.
                $existing = $this->findReceipt($rep, $key, true);
                if ($existing !== null) {
                    if ($existing->response === null) {
                        return [
                            'key' => $key,
                            'status' => 'conflict',
                            'error' => 'This operation needs support review before it can be retried.',
                        ];
                    }

                    if ($existing->payload_hash !== null && $payloadHash !== null && $existing->payload_hash !== $payloadHash) {
                        return ['key' => $key, 'status' => 'mismatch', 'error' => 'Payload mismatch for same idempotency key'];
                    }

                    return ['key' => $key, 'status' => 'duplicate', 'result' => $existing->response];
                }

                $receipt = SyncReceipt::create([
                    'company_id' => $rep->activeCompanyId(),
                    'user_id' => $rep->id,
                    'idempotency_key' => $key,
                    'operation_type' => $type,
                    'protocol_version' => $protocolVersion,
                    'payload_hash' => $payloadHash,
                    'device_id' => $deviceId,
                    'response' => null,
                ]);

                $result = $this->registry->get($type)->handle($rep, $payload, $key);
                $receipt->update(['response' => $result]);

                return ['key' => $key, 'status' => 'applied', 'result' => $result];
            }, attempts: 3);
        } catch (ValidationException) {
            return $this->failure(
                $rep,
                $key,
                $type,
                'sync_validation_failed',
                __('app.sync_validation_failed'),
            );
        } catch (QueryException $e) {
            // A concurrent request can pass the initial lookup, then lose the
            // database's unique (company_id, idempotency_key) race. Re-read the
            // durable receipt so the client receives its original outcome.
            $existing = $this->findReceipt($rep, $key);
            if ($existing?->response !== null) {
                if ($existing->payload_hash !== null && $payloadHash !== null && $existing->payload_hash !== $payloadHash) {
                    return ['key' => $key, 'status' => 'mismatch', 'error' => 'Payload mismatch for same idempotency key'];
                }

                return ['key' => $key, 'status' => 'duplicate', 'result' => $existing->response];
            }

            if ($existing !== null) {
                return [
                    'key' => $key,
                    'status' => 'conflict',
                    'error' => 'This operation needs support review before it can be retried.',
                ];
            }

            return $this->failure(
                $rep,
                $key,
                $type,
                'sync_storage_failed',
                __('app.sync_storage_failed'),
                $e,
            );
        } catch (\Throwable $e) {
            return $this->failure(
                $rep,
                $key,
                $type,
                'sync_processing_failed',
                __('app.sync_processing_failed'),
                $e,
            );
        }
    }

    /**
     * Return a stable client-safe failure while retaining diagnostic context in
     * server logs. Payloads and exception messages never cross the API boundary.
     *
     * @return array{key: string, status: string, error_code: string, error: string}
     */
    private function failure(
        User $rep,
        string $key,
        string $type,
        string $code,
        string $message,
        ?\Throwable $exception = null,
    ): array {
        if ($exception !== null) {
            Log::error('Offline sync operation failed.', [
                'company_id' => $rep->activeCompanyId(),
                'user_id' => $rep->id,
                'operation_type' => $type,
                'idempotency_key' => $key,
                'error_code' => $code,
                'exception' => $exception,
            ]);
        }

        return [
            'key' => $key,
            'status' => 'failed',
            'error_code' => $code,
            'error' => $message,
        ];
    }

    private function findReceipt(User $rep, string $key, bool $lock = false): ?SyncReceipt
    {
        $query = SyncReceipt::withoutGlobalScopes()
            ->where('company_id', $rep->activeCompanyId())
            ->where('idempotency_key', $key);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
