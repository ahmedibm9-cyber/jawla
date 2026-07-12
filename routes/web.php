<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response('ok', 200));

// Admin (Filament) is auto-registered by the panel provider.

// Rep PWA route group
Route::middleware(['web'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', function () {
        return view('layouts.app', ['slot' => '']);
    });
});
