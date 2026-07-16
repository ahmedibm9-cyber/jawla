<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\PriceQuotation;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Services\ComplaintService;
use App\Services\Contracts\AlarmService;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceService;
use App\Services\Contracts\PricingService;
use App\Services\Contracts\StockService;
use App\Services\NumberSequenceService;
use App\Services\StockService as StockServiceImpl;
use App\Support\ActiveCompanyContext;
use Filament\Events\Auth\Login as FilamentLogin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveCompanyContext::class);

        $this->app->bind(StockService::class, StockServiceImpl::class);
        $this->app->bind(InvoiceService::class, fn () => app(\App\Services\InvoiceService::class));
        $this->app->bind(PricingService::class, fn () => app(\App\Services\PricingService::class));
        $this->app->bind(DocumentNumberService::class, fn () => app(NumberSequenceService::class));
        $this->app->bind(AlarmService::class, fn () => app(\App\Services\AlarmService::class));
        $this->app->singleton(ComplaintService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        Event::listen(FilamentLogin::class, function (FilamentLogin $event): void {
            Activity::log('login', $event->user, "Admin login: {$event->user->email}");
        });
    }
}
