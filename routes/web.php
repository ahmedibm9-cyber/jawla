<?php

use App\Http\Controllers\App\LoginController;
use App\Livewire\App\Home;
use App\Livewire\App\VisitFlow;
use App\Livewire\App\TodaysCustomers;
use App\Livewire\App\QuotationFlow;
use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response('ok', 200));

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'ar'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

// Admin (Filament) is auto-registered by the panel provider.

// Rep PWA route group
Route::middleware(['web', 'guest'])->prefix('app')->name('app.')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware(['web', 'auth', 'ensure.rep'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/visit/{visit}', VisitFlow::class)->name('visit');
    Route::get('/customers', TodaysCustomers::class)->name('customers');
    Route::get('/quotations', QuotationFlow::class)->name('quotations');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
