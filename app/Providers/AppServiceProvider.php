<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\PriceQuotation;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Services\ComplaintService;
use App\Services\Contracts\AlarmService;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceCalculationService;
use App\Services\Contracts\InvoiceService;
use App\Services\Contracts\PricingService;
use App\Services\Contracts\StockService;
use App\Services\Contracts\VanTransferService as VanTransferServiceContract;
use App\Services\Eta\Contracts\EtaClient;
use App\Services\Eta\Contracts\EtaSigner;
use App\Services\Eta\HttpEtaClient;
use App\Services\Eta\NullEtaClient;
use App\Services\Eta\UnsignedEtaSigner;
use App\Services\InvoiceCalculationService as InvoiceCalculationServiceImpl;
use App\Services\NumberSequenceService;
use App\Services\OutOfStockService;
use App\Services\StockService as StockServiceImpl;
use App\Services\Sync\SyncHandlerRegistry;
use App\Services\VanTransferService;
use App\Support\ActiveCompanyContext;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Events\Auth\Login as FilamentLogin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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

        // ETA e-invoicing. The HTTP transport (OAuth + submission + response
        // mapping) is built; it activates only when ETA is enabled AND base URLs
        // are configured, so demo/UAT stays on the inert NullEtaClient. The
        // document signer stays Unsigned until the taxpayer certificate is
        // provisioned — the last go-live gate. No call-site changes required.
        $this->app->bind(EtaSigner::class, UnsignedEtaSigner::class);
        $this->app->bind(EtaClient::class, function () {
            $configured = (bool) config('eta.enabled')
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

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // Rep offline-sync endpoint (CG2), same guard stack as the rep PWA group.
        Route::middleware(['web', 'auth', 'ensure.rep'])
            ->prefix('app')
            ->name('app.')
            ->group(base_path('routes/rep-sync.php'));

        Event::listen(FilamentLogin::class, function (FilamentLogin $event): void {
            Activity::log('login', $event->user, "Admin login: {$event->user->email}");
        });

        // Fail-fast: validate critical env vars in production
        if (env('APP_ENV') === 'production') {
            $required = ['APP_KEY', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
            foreach ($required as $var) {
                if (empty(env($var))) {
                    throw new \RuntimeException("Missing required environment variable: {$var}");
                }
            }
        }
    }
}
