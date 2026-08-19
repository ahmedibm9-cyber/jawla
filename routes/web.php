<?php

use App\Filament\Auth\Pages\Login;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\App\LoginController;
use App\Http\Controllers\App\PdfController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\LicenseRecoveryController;
use App\Http\Controllers\SystemPageController;
use App\Livewire\App\AddCustomer;
use App\Livewire\App\CashReconcile;
use App\Livewire\App\CollectPayment;
use App\Livewire\App\CreateSalesOrder;
use App\Livewire\App\DeviceRegistration;
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
use App\Livewire\App\Tasks;
use App\Livewire\App\TodaysCustomers;
use App\Livewire\App\VanTransfers;
use App\Livewire\App\VisitFlow;
use App\Livewire\App\Visits;
use Illuminate\Support\Facades\Route;

Route::get('/', [SystemPageController::class, 'root']);

// Unified login — serves the Filament login page directly at /login.
// Reps and admins both authenticate here; LoginResponse redirects by role.
Route::get('/login', Login::class)->name('login');

// Catch /admin root — Filament registers sub-pages but not the bare prefix
Route::get('/admin', [SystemPageController::class, 'adminRoot']);

Route::get('/offline', [SystemPageController::class, 'offline']);

Route::get('/health', [SystemPageController::class, 'health']);

// Temporary debug — remove after verifying seed
if (app()->environment('production')) {
    Route::get('/_debug/users', function () {
        $users = \App\Models\User::select('id', 'email', 'is_active', 'password', 'created_at')->get();
        $mode = config('jawla.mode');
        $isDemo = config('jawla.is_demo');
        $envMode = env('JAWLA_MODE');
        return response()->json([
            'mode' => $mode,
            'is_demo' => $isDemo,
            'env_jawla_mode' => $envMode,
            'user_count' => $users->count(),
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'email' => $u->email,
                'is_active' => $u->is_active,
                'has_password' => filled($u->password),
                'password_is_hashed' => \Illuminate\Support\Facades\Hash::isHashed($u->password),
                'created_at' => $u->created_at,
            ]),
        ]);
    });

    Route::get('/_debug/seed', function () {
        ob_start();
        try {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
            $output = ob_get_clean();
            return response()->json([
                'status' => 'ok',
                'output' => $output,
                'artisan_output' => \Illuminate\Support\Facades\Artisan::output(),
            ]);
        } catch (\Throwable $e) {
            $output = ob_get_clean();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'output' => $output,
            ]);
        }
    });
}

Route::get('/locale/{locale}', [SystemPageController::class, 'switchLocale'])
    ->middleware('throttle:10,1')
    ->name('locale.switch');

Route::post('/company/switch', [CompanyContextController::class, 'update'])
    ->middleware(['auth', 'license', 'throttle:post'])
    ->name('company.switch');

Route::post('/api/onboarding/complete', [OnboardingController::class, 'complete'])
    ->middleware(['auth', 'license', 'throttle:post'])
    ->name('api.onboarding.complete');

// Admin (Filament) is auto-registered by the panel provider.

// Handle GET /admin/logout — Filament registers POST only
Route::get('/admin/logout', [SystemPageController::class, 'adminLogout'])
    ->middleware('throttle:10,1');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/admin/license-recovery', [LicenseRecoveryController::class, 'create'])->name('license.recovery');
    Route::post('/admin/license-recovery', [LicenseRecoveryController::class, 'store'])
        ->middleware('throttle:post')->name('license.recovery.store');
});

// Old rep login route — redirect to unified login
Route::get('/app/login', [SystemPageController::class, 'appLoginRedirect'])->name('app.login');

// Old /app/sales-flow URL — redirect to /app/sell
Route::get('/app/sales-flow', [SystemPageController::class, 'salesFlowRedirect']);

// Rep PWA route group (protected)
Route::middleware(['web', 'auth', 'license', 'ensure.rep', 'ensure.device'])->prefix('app')->name('app.')->group(function () {
    Route::get('/device', DeviceRegistration::class)->name('device');
    Route::get('/', Home::class)->name('home');
    Route::get('/visit/{visit}', VisitFlow::class)->name('visit');
    Route::get('/customers', TodaysCustomers::class)->name('customers');
    Route::get('/visits', Visits::class)->name('visits');
    Route::get('/orders', Orders::class)->name('orders');
    Route::get('/orders/create', CreateSalesOrder::class)->name('orders.create');
    Route::get('/notifications', Notifications::class)->name('notifications');
    Route::get('/tasks', Tasks::class)->name('tasks');
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
