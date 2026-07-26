<?php

use App\Http\Controllers\App\LoginController;
use App\Http\Controllers\App\PdfController;
use App\Http\Controllers\SystemPageController;
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
use App\Livewire\App\ProfilePage;
use App\Livewire\App\QuotationFlow;
use App\Livewire\App\SalesFlow;
use App\Livewire\App\SettingsPage;
use App\Livewire\App\StockSearch;
use App\Livewire\App\SubmitPurchaseOffer;
use App\Livewire\App\SyncQueue;
use App\Livewire\App\TodaysCustomers;
use App\Livewire\App\VanTransfers;
use App\Livewire\App\VisitFlow;
use App\Livewire\App\Visits;
use Illuminate\Support\Facades\Route;

Route::get('/', [SystemPageController::class, 'root']);

// Unified login — Filament's built-in login page is the only login page.
// Reps and admins both authenticate here; LoginResponse redirects by role.
Route::get('/login', fn () => redirect()->route('filament.admin.auth.login'))->name('login');

// Catch /admin root — Filament registers sub-pages but not the bare prefix
Route::get('/admin', [SystemPageController::class, 'adminRoot']);

Route::get('/offline', [SystemPageController::class, 'offline']);

Route::get('/health', [SystemPageController::class, 'health']);

Route::get('/locale/{locale}', [SystemPageController::class, 'switchLocale'])
    ->middleware('throttle:10,1')
    ->name('locale.switch');

// Admin (Filament) is auto-registered by the panel provider.

// Handle GET /admin/logout — Filament registers POST only
Route::get('/admin/logout', [SystemPageController::class, 'adminLogout'])
    ->middleware('throttle:10,1');

// Old rep login route — redirect to unified login
Route::get('/app/login', fn () => redirect()->route('login'))->name('app.login');

// Old /app/sales-flow URL — redirect to /app/sell
Route::get('/app/sales-flow', fn () => redirect()->route('app.sell'));

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
    Route::get('/sync-queue', SyncQueue::class)->name('sync-queue');
    Route::get('/more', MorePage::class)->name('more');
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/settings', SettingsPage::class)->name('settings');
    Route::get('/customers/create', AddCustomer::class)->name('customers.create');
    Route::get('/complaints', LogComplaint::class)->name('complaints');
    Route::get('/collect-payment', CollectPayment::class)->name('collect-payment');
    Route::get('/sell', SalesFlow::class)->name('sell');
    Route::get('/sell/{customer}', SalesFlow::class)->name('sell.customer');
    Route::get('/returns', LogReturn::class)->name('returns');
    Route::get('/expenses', LogExpense::class)->name('expenses');
    Route::get('/reconcile', CashReconcile::class)->name('reconcile');
    Route::get('/transfers', VanTransfers::class)->name('transfers');
    Route::get('/purchase-offer', SubmitPurchaseOffer::class)->name('purchase-offer');
    Route::get('/pdf/proforma/{proforma}', [PdfController::class, 'proforma'])
        ->middleware('throttle:10,1')
        ->name('pdf.proforma');
    Route::get('/pdf/invoice/{invoice}', [PdfController::class, 'invoice'])
        ->middleware('throttle:10,1')
        ->name('pdf.invoice');
    Route::get('/pdf/receipt/{payment}', [PdfController::class, 'receipt'])
        ->middleware('throttle:10,1')
        ->name('pdf.receipt');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
