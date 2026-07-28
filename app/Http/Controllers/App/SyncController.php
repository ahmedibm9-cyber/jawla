<?php

namespace App\Http\Controllers\App;

use App\Services\Sync\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Offline sync ingest for the rep PWA (CG2). Accepts a batch of queued
 * operations and returns a per-operation result so the client can reconcile its
 * outbox. Exactly-once application is handled by SyncService; this controller
 * only validates the envelope and delegates.
 */
class SyncController
{
    public function store(Request $request, SyncService $sync): JsonResponse
    {
        $data = $request->validate([
            'operations' => ['required', 'array', 'max:100'],
            'operations.*.key' => ['required', 'string', 'max:190'],
            'operations.*.type' => ['required', 'string', 'max:100'],
            'operations.*.payload' => ['nullable', 'array'],
            'operations.*.payloadHash' => ['nullable', 'string', 'size:64'],
            'operations.*.deviceId' => ['nullable', 'string', 'max:190'],
        ]);

        $deviceId = $request->header('X-Device-Id');

        $operations = array_map(fn ($op) => [
            'key' => $op['key'],
            'type' => $op['type'],
            'payload' => $op['payload'] ?? [],
            'payload_hash' => $op['payloadHash'] ?? null,
            'device_id' => $op['deviceId'] ?? $deviceId,
        ], $data['operations']);

        $results = $sync->process(Auth::user(), $operations, (int) $request->header('X-Sync-Protocol-Version', 1));

        return response()->json(['results' => $results]);
    }
}
