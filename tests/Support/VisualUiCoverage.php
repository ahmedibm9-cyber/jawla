<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Filament\Pages\ActivityLog;
use App\Filament\Pages\AdminPreferences;
use App\Filament\Pages\ApiTokens;
use App\Filament\Pages\CollectPayment as AdminCollectPayment;
use App\Filament\Pages\CustomerMap;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EtaSettings;
use App\Filament\Pages\RepLiveMap;
use App\Filament\Pages\ReportsPage;
use App\Filament\Pages\SessionManagement;
use App\Filament\Pages\StockImport;
use App\Filament\Pages\SupplierComparison;
use App\Filament\Resources\AlarmResource;
use App\Filament\Resources\BatchResource;
use App\Filament\Resources\CashReconciliationResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\ComplaintResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\DailyVisitAssignmentResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\GoodsInTransitResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PriceQuotationRequestResource;
use App\Filament\Resources\ProductPriceResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\PurchaseRequestResource;
use App\Filament\Resources\ReturnRecordResource;
use App\Filament\Resources\RouteResource;
use App\Filament\Resources\SalesTargetResource;
use App\Filament\Resources\StockResource;
use App\Filament\Resources\TaskResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VanTransferResource;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\OpenAlarmsWidget;
use App\Filament\Widgets\OutstandingBalanceWidget;
use App\Filament\Widgets\PendingQuotationsWidget;
use App\Filament\Widgets\RepPerformanceWidget;
use App\Filament\Widgets\RepRoiWidget;
use App\Filament\Widgets\SalesTodayWidget;
use App\Filament\Widgets\VisitsTodayWidget;

/**
 * Executable inventory for docs/TEST_PLAN.md.
 *
 * Tests compare this manifest with the application's routes and class
 * inventory so a newly added screen cannot silently escape the visual UI
 * campaign.
 */
final class VisualUiCoverage
{
    /** @return list<string> */
    public static function locales(): array
    {
        return ['ar', 'en'];
    }

    /** @return list<string> */
    public static function roles(): array
    {
        return [
            'unauthenticated',
            'super_admin',
            'admin',
            'sales_manager',
            'accounts',
            'purchasing',
            'warehouse_keeper',
            'executive',
            'hr_admin',
            'system_viewer',
            'sales_rep',
            'rep',
            'disabled',
            'multi_company',
        ];
    }

    /** @return list<string> */
    public static function routeNames(): array
    {
        return [
            'login',
            'locale.switch',
            'company.switch',
            'api.onboarding.complete',
            'app.login',
            'app.home',
            'app.visit',
            'app.customers',
            'app.visits',
            'app.orders',
            'app.notifications',
            'app.quotations',
            'app.stock',
            'app.sync',
            'app.sync-queue',
            'app.more',
            'app.profile',
            'app.settings',
            'app.customers.create',
            'app.complaints',
            'app.collect-payment',
            'app.sell',
            'app.sell.customer',
            'app.returns',
            'app.expenses',
            'app.reconcile',
            'app.transfers',
            'app.purchase-offer',
            'app.pdf.proforma',
            'app.pdf.invoice',
            'app.pdf.receipt',
            'app.push-subscriptions.destroy',
            'app.push-subscriptions.store',
            'app.logout',
            'app.offline-snapshot',
        ];
    }

    /** @return list<class-string> */
    public static function adminResources(): array
    {
        return [
            AlarmResource::class,
            BatchResource::class,
            CashReconciliationResource::class,
            CompanyResource::class,
            ComplaintResource::class,
            CustomerResource::class,
            DailyVisitAssignmentResource::class,
            ExpenseResource::class,
            GoodsInTransitResource::class,
            InvoiceResource::class,
            PaymentResource::class,
            PriceQuotationRequestResource::class,
            ProductPriceResource::class,
            ProductResource::class,
            ProformaInvoiceResource::class,
            PurchaseOrderResource::class,
            PurchaseRequestResource::class,
            ReturnRecordResource::class,
            RouteResource::class,
            SalesTargetResource::class,
            StockResource::class,
            TaskResource::class,
            UserResource::class,
            VanTransferResource::class,
        ];
    }

    /** @return list<class-string> */
    public static function adminPages(): array
    {
        return [
            ActivityLog::class,
            AdminPreferences::class,
            ApiTokens::class,
            AdminCollectPayment::class,
            CustomerMap::class,
            Dashboard::class,
            EtaSettings::class,
            RepLiveMap::class,
            ReportsPage::class,
            SessionManagement::class,
            StockImport::class,
            SupplierComparison::class,
        ];
    }

    /** @return list<class-string> */
    public static function dashboardWidgets(): array
    {
        return [
            LowStockAlertWidget::class,
            OpenAlarmsWidget::class,
            OutstandingBalanceWidget::class,
            PendingQuotationsWidget::class,
            RepPerformanceWidget::class,
            RepRoiWidget::class,
            SalesTodayWidget::class,
            VisitsTodayWidget::class,
        ];
    }

    /** @return array<string, array{width: int, height: int}> */
    public static function viewports(): array
    {
        return [
            'mobile-small' => ['width' => 320, 'height' => 568],
            'mobile' => ['width' => 390, 'height' => 844],
            'tablet' => ['width' => 768, 'height' => 1024],
            'laptop-14' => ['width' => 1366, 'height' => 768],
            'desktop' => ['width' => 1920, 'height' => 1080],
        ];
    }

    /** @return list<string> */
    public static function states(): array
    {
        return [
            'loading',
            'empty',
            'populated',
            'validation-error',
            'permission-denied',
            'not-found',
            'server-error',
            'slow-network',
            'offline',
            'sync-pending',
            'sync-failed',
            'sync-conflict',
            'expired-session',
            'large-data',
        ];
    }

    /** @return list<string> */
    public static function workflows(): array
    {
        return [
            'WF-01',
            'WF-02',
            'WF-03',
            'WF-04',
            'WF-05',
            'WF-06',
            'WF-07',
            'WF-08',
            'WF-09',
            'WF-10',
            'WF-11',
            'WF-12',
            'WF-13',
            'WF-14',
            'WF-15',
            'WF-16',
            'WF-17',
            'WF-18',
            'WF-19',
        ];
    }
}
