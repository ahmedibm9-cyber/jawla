<?php

use App\Http\Controllers\App\LoginController;
use App\Http\Controllers\App\PdfController;
use App\Livewire\App\AddCustomer;
use App\Livewire\App\CashReconcile;
use App\Livewire\App\CollectPayment;
use App\Livewire\App\Home;
use App\Livewire\App\LogComplaint;
use App\Livewire\App\LogExpense;
use App\Livewire\App\LogReturn;
use App\Livewire\App\MorePage;
use App\Livewire\App\Notifications;
use App\Livewire\App\Orders;
use App\Livewire\App\QuotationFlow;
use App\Livewire\App\SalesFlow;
use App\Livewire\App\StockSearch;
use App\Livewire\App\SubmitPurchaseOffer;
use App\Livewire\App\TodaysCustomers;
use App\Livewire\App\VisitFlow;
use App\Livewire\App\Visits;
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

Route::get('/offline', fn () => view('vendor.laravel.offline'));

Route::get('/health', function () {
    return response('ok', 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'no-store, private');
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
    Route::get('/visits', Visits::class)->name('visits');
    Route::get('/orders', Orders::class)->name('orders');
    Route::get('/notifications', Notifications::class)->name('notifications');
    Route::get('/quotations', QuotationFlow::class)->name('quotations');
    Route::get('/stock', StockSearch::class)->name('stock');
    Route::get('/more', MorePage::class)->name('more');
    Route::get('/customers/create', AddCustomer::class)->name('customers.create');
    Route::get('/complaints', LogComplaint::class)->name('complaints');
    Route::get('/collect-payment', CollectPayment::class)->name('collect-payment');
    Route::get('/sell', SalesFlow::class)->name('sell');
    Route::get('/sell/{customer}', SalesFlow::class)->name('sell.customer');
    Route::get('/returns', LogReturn::class)->name('returns');
    Route::get('/expenses', LogExpense::class)->name('expenses');
    Route::get('/reconcile', CashReconcile::class)->name('reconcile');
    Route::get('/purchase-offer', SubmitPurchaseOffer::class)->name('purchase-offer');
    Route::get('/pdf/proforma/{proforma}', [PdfController::class, 'proforma'])->name('pdf.proforma');
    Route::get('/pdf/invoice/{invoice}', [PdfController::class, 'invoice'])->name('pdf.invoice');
    Route::get('/pdf/receipt/{payment}', [PdfController::class, 'receipt'])->name('pdf.receipt');
    Route::match(['get', 'post'], '/logout', [LoginController::class, 'destroy'])->name('logout');
});
