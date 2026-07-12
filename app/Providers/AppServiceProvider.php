<?php

namespace App\Providers;

use App\Services\Contracts\AlarmService;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceService;
use App\Services\Contracts\LandedCostService;
use App\Services\Contracts\PaymentService;
use App\Services\Contracts\PricingService;
use App\Services\Contracts\StockService;
use App\Services\StockService as StockServiceImpl;
use App\Support\ActiveCompanyContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveCompanyContext::class);

        $this->app->bind(StockService::class, StockServiceImpl::class);
        $this->app->bind(InvoiceService::class, fn () => app(\App\Services\InvoiceService::class));
        $this->app->bind(PaymentService::class, fn () => app(\App\Services\PaymentService::class));
        $this->app->bind(PricingService::class, fn () => app(\App\Services\PricingService::class));
        $this->app->bind(DocumentNumberService::class, fn () => app(\App\Services\NumberSequenceService::class));
        $this->app->bind(LandedCostService::class, fn () => app(\App\Services\LandedCostService::class));
        $this->app->bind(AlarmService::class, fn () => app(\App\Services\AlarmService::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
