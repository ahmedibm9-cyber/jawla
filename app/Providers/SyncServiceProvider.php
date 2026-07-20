<?php

namespace App\Providers;

use App\Services\Sync\Handlers\ComplaintSyncHandler;
use App\Services\Sync\Handlers\ExpenseSyncHandler;
use App\Services\Sync\Handlers\PaymentSyncHandler;
use App\Services\Sync\Handlers\ReturnSyncHandler;
use App\Services\Sync\Handlers\SaleSyncHandler;
use App\Services\Sync\Handlers\VisitReportSyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the offline-sync operation handlers (CG2). Each type maps to a rep
 * write and reuses the existing service. Adding a new offline operation = add a
 * handler + one line here.
 */
class SyncServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $registry = $this->app->make(SyncHandlerRegistry::class);

        $registry->register('sale', $this->app->make(SaleSyncHandler::class));
        $registry->register('payment', $this->app->make(PaymentSyncHandler::class));
        $registry->register('return', $this->app->make(ReturnSyncHandler::class));
        $registry->register('expense', $this->app->make(ExpenseSyncHandler::class));
        $registry->register('complaint', $this->app->make(ComplaintSyncHandler::class));
        $registry->register('visit_report', $this->app->make(VisitReportSyncHandler::class));
    }
}
