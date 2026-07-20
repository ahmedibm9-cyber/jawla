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
        ]);

        $results = $sync->process(Auth::user(), $data['operations']);

        return response()->json(['results' => $results]);
    }
}
