<?php

use App\Http\Controllers\App\PdfController;
use App\Livewire\App\AddCustomer;
use App\Livewire\App\Home;
use App\Livewire\App\LogComplaint;
use App\Livewire\App\MorePage;
use App\Livewire\App\QuotationFlow;
use App\Livewire\App\StockSearch;
use App\Livewire\App\SubmitPurchaseOffer;
use App\Livewire\App\TodaysCustomers;
use App\Livewire\App\VisitFlow;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin/login'));

// Catch /admin root — Filament registers sub-pages but not the bare prefix
Route::get('/admin', function () {
    $user = auth()->user();

    if ($user && $user->hasRole('rep')) {
        return redirect('/app');
    }

    if ($user && method_exists($user, 'canAccessPanel')) {
        return redirect('/admin/dashboard');
    }

    return redirect('/admin/login');
});

Route::get('/up', fn () => response('ok', 200));

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'ar'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

// Admin (Filament) is auto-registered by the panel provider.

// Handle GET /admin/logout — Filament registers POST only
Route::get('/admin/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/admin/login');
});

// Rep PWA route group (protected)
Route::middleware(['web', 'auth', 'ensure.rep'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/visit/{visit}', VisitFlow::class)->name('visit');
    Route::get('/customers', TodaysCustomers::class)->name('customers');
    Route::get('/quotations', QuotationFlow::class)->name('quotations');
    Route::get('/stock', StockSearch::class)->name('stock');
    Route::get('/more', MorePage::class)->name('more');
    Route::get('/customers/create', AddCustomer::class)->name('customers.create');
    Route::get('/complaints', LogComplaint::class)->name('complaints');
    Route::get('/purchase-offer', SubmitPurchaseOffer::class)->name('purchase-offer');
    Route::get('/pdf/proforma/{proforma}', [PdfController::class, 'proforma'])->name('pdf.proforma');
    Route::get('/pdf/invoice/{invoice}', [PdfController::class, 'invoice'])->name('pdf.invoice');
    Route::match(['get', 'post'], '/logout', [App\Http\Controllers\App\LoginController::class, 'destroy'])->name('logout');
});
