<?php

use App\Http\Controllers\App\SyncController;
use Illuminate\Support\Facades\Route;

/*
| Rep offline-sync endpoint. Registered from AppServiceProvider with the same
| stack as the rep PWA group (web + auth + ensure.rep) plus the POST throttle,
| kept in its own file so the sync surface stays self-contained.
*/
Route::post('sync', [SyncController::class, 'store'])
    ->middleware('throttle:post')
    ->name('sync');
