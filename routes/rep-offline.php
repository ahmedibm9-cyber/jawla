<?php

use App\Http\Controllers\App\OfflineSnapshotController;
use App\Http\Controllers\App\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
| Rep offline-snapshot endpoint. Returns a compact JSON payload of the
| rep's read data for client-side IndexedDB caching. Registered from
| AppServiceProvider with the same stack as the rep PWA group.
*/
Route::get('offline-snapshot', OfflineSnapshotController::class)
    ->middleware('throttle:post')
    ->name('offline-snapshot');

Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])
    ->middleware('throttle:push-subscriptions')
    ->name('push-subscriptions.store');

Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])
    ->name('push-subscriptions.destroy');
