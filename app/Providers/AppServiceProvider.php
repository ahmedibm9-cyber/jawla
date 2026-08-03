<?php

namespace App\Providers;

use App\Http\Middleware\EnsureApprovedDevice;
use App\Models\Activity;
use App\Models\PriceQuotation;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Services\ComplaintService;
use App\Services\Contracts\AlarmService;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceCalculationService;
use App\Services\Contracts\InvoiceService;
use App\Services\Contracts\PaymentService as PaymentServiceContract;
use App\Services\Contracts\PricingService;
use App\Services\Contracts\PushGateway;
use App\Services\Contracts\StockService;
use App\Services\Contracts\VanTransferService as VanTransferServiceContract;
use App\Services\Eta\Contracts\EtaClient;
use App\Services\Eta\Contracts\EtaSigner;
use App\Services\Eta\HttpEtaClient;
use App\Services\Eta\NullEtaClient;
use App\Services\Eta\UnsignedEtaSigner;
use App\Services\HttpPushGateway;
use App\Services\InvoiceCalculationService as InvoiceCalculationServiceImpl;
use App\Services\NumberSequenceService;
use App\Services\OutOfStockService;
use App\Services\PaymentService;
use App\Services\StockService as StockServiceImpl;
use App\Services\Sync\SyncHandlerRegistry;
use App\Services\VanTransferService;
use App\Support\ActiveCompanyContext;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveCompanyContext::class);
        $this->app->singleton(SyncHandlerRegistry::class);

        $this->app->bind(
            LoginResponse::class,
            \App\Filament\Auth\Http\Responses\LoginResponse::class,
        );

        $this->app->bind(StockService::class, StockServiceImpl::class);
        $this->app->bind(InvoiceService::class, fn () => app(\App\Services\InvoiceService::class));
        $this->app->bind(InvoiceCalculationService::class, InvoiceCalculationServiceImpl::class);
        $this->app->bind(PricingService::class, fn () => app(\App\Services\PricingService::class));
        $this->app->bind(DocumentNumberService::class, fn () => app(NumberSequenceService::class));
        $this->app->bind(AlarmService::class, fn () => app(\App\Services\AlarmService::class));
        $this->app->bind(\App\Services\Contracts\OutOfStockService::class, fn () => app(OutOfStockService::class));
        $this->app->singleton(ComplaintService::class);
        $this->app->bind(VanTransferServiceContract::class, fn () => app(VanTransferService::class));
        $this->app->bind(PaymentServiceContract::class, fn () => app(PaymentService::class));
        $this->app->bind(PushGateway::class, HttpPushGateway::class);

        // ETA e-invoicing. The HTTP transport (OAuth + submission + response
        // mapping) is built; it activates only when ETA is enabled AND base URLs
        // are configured, so demo/UAT stays on the inert NullEtaClient. The
        // document signer stays Unsigned until the taxpayer certificate is
        // provisioned — the last go-live gate. No call-site changes required.
        $this->app->bind(EtaSigner::class, UnsignedEtaSigner::class);
        $this->app->bind(EtaClient::class, function () {
            $configured = ! config('jawla.is_demo')
                && (bool) config('eta.enabled')
                && (string) config('eta.api_base_url') !== ''
                && (string) config('eta.id_base_url') !== '';

            return $configured ? app(HttpEtaClient::class) : app(NullEtaClient::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware(EnsureApprovedDevice::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.pwa-head')->render(),
        );

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Model::preventLazyLoading(! app()->isProduction());

        User::observe(AuditObserver::class);
        PriceQuotation::observe(AuditObserver::class);

        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('post', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });

        // Public API v1: throttled per token (falls back to IP for unauthenticated
        // hits on auth:sanctum). Routes registered here rather than in
        // bootstrap/app.php to keep the API surface self-contained.
        RateLimiter::for('api', function (Request $request) {
            $token = $request->user()?->currentAccessToken();
            $key = $token ? 'token:'.$token->getKey() : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });

        // Financial mutations — stricter than global POST (30/min per user)
        RateLimiter::for('financial', function (Request $request) {
            $user = $request->user();
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return Limit::perMinute(30)->by($key);
        });

        // Sync batch — tighter due to 100 ops per call (20/min per user)
        RateLimiter::for('sync', function (Request $request) {
            return Limit::perMinute(20)->by('user:'.$request->user()->id);
        });

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // Rep offline-sync endpoint (CG2), same guard stack as the rep PWA group.
        Route::middleware(['web', 'auth', 'ensure.rep'])
            ->prefix('app')
            ->name('app.')
            ->group(base_path('routes/rep-sync.php'));

        // Rep offline-snapshot endpoint — returns cached read data for IndexedDB.
        Route::middleware(['web', 'auth', 'ensure.rep'])
            ->prefix('app')
            ->name('app.')
            ->group(base_path('routes/rep-offline.php'));

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            Activity::log('login', $event->user, "Login: {$event->user->email}");
        });

        // Per-user admin sidebar section order (set on the "Customize interface"
        // page). Gated on a saved preference and wrapped so a malformed value can
        // never break panel navigation for anyone.
        Filament::serving(function (): void {
            try {
                $order = auth()->user()?->preference('nav_group_order');

                if (is_array($order) && $order !== []) {
                    Filament::getPanel('admin')->navigationGroups($order);
                }
            } catch (\Throwable) {
                // Intentionally ignored — fall back to the default group order.
            }
        });

        // Fail fast against cached configuration in production. Reading env()
        // here would stop working after `php artisan config:cache`.
        if (app()->isProduction()) {
            $connection = (string) config('database.default');
            $required = [
                'APP_KEY' => config('app.key'),
                'DB_HOST' => config("database.connections.{$connection}.host"),
                'DB_DATABASE' => config("database.connections.{$connection}.database"),
                'DB_USERNAME' => config("database.connections.{$connection}.username"),
            ];

            foreach ($required as $name => $value) {
                if (blank($value)) {
                    throw new \RuntimeException("Missing required environment variable: {$name}");
                }
            }
        }
    }
}
