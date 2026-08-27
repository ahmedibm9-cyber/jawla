<?php

namespace App\Providers;

use App\Services\Sync\Handlers\CallSyncHandler;
use App\Services\Sync\Handlers\CollectionSubmissionSyncHandler;
use App\Services\Sync\Handlers\ComplaintSyncHandler;
use App\Services\Sync\Handlers\ExpenseSyncHandler;
use App\Services\Sync\Handlers\PaymentSyncHandler;
use App\Services\Sync\Handlers\RequestSyncHandler;
use App\Services\Sync\Handlers\ReturnRequestSyncHandler;
use App\Services\Sync\Handlers\ReturnSyncHandler;
use App\Services\Sync\Handlers\SalesOrderSyncHandler;
use App\Services\Sync\Handlers\SaleSyncHandler;
use App\Services\Sync\Handlers\TicketSyncHandler;
use App\Services\Sync\Handlers\TodoSyncHandler;
use App\Services\Sync\Handlers\VisitReportSyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the offline-sync operation handlers (CG2) via container tagging.
 * The SyncHandlerRegistry auto-discovers tagged handlers at first use.
 * Adding a new offline operation = create the handler class + add it to the tag
 * list below — no manual registry calls, no boot-order dependencies.
 */
class SyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            SaleSyncHandler::class,
            SalesOrderSyncHandler::class,
            PaymentSyncHandler::class,
            CollectionSubmissionSyncHandler::class,
            ReturnSyncHandler::class,
            ReturnRequestSyncHandler::class,
            ExpenseSyncHandler::class,
            ComplaintSyncHandler::class,
            VisitReportSyncHandler::class,
            // M7: Competitor gap closure
            TodoSyncHandler::class,
            TicketSyncHandler::class,
            RequestSyncHandler::class,
            CallSyncHandler::class,
        ], 'sync.handler');
    }
}
