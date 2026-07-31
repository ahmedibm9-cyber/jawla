<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\AlarmResource;
use App\Filament\Resources\DailyVisitAssignmentResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PriceQuotationRequestResource;
use App\Filament\Resources\StockResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\OpenAlarmsWidget;
use App\Filament\Widgets\OutstandingBalanceWidget;
use App\Filament\Widgets\PendingQuotationsWidget;
use App\Filament\Widgets\RepPerformanceWidget;
use App\Filament\Widgets\SalesTodayWidget;
use App\Filament\Widgets\VisitsTodayWidget;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_sales_today_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(SalesTodayWidget::class)
            ->assertSuccessful();
    }

    public function test_visits_today_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(VisitsTodayWidget::class)
            ->assertSuccessful();
    }

    public function test_outstanding_balance_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(OutstandingBalanceWidget::class)
            ->assertSuccessful();
    }

    public function test_low_stock_alert_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(LowStockAlertWidget::class)
            ->assertSuccessful();
    }

    public function test_rep_performance_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(RepPerformanceWidget::class)
            ->assertSuccessful();
    }

    public function test_open_alarms_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(OpenAlarmsWidget::class)
            ->assertSuccessful();
    }

    public function test_pending_quotations_widget_renders_for_admin(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $this->actingAs($admin);

        Livewire::test(PendingQuotationsWidget::class)
            ->assertSuccessful();
    }

    public function test_admin_can_reorder_and_hide_dashboard_widgets_from_the_customization_utility(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(Dashboard::class)
            ->assertActionExists('customizeDashboard')
            ->call('saveDashboardCustomization', [
                ['key' => SalesTodayWidget::class, 'visible' => true],
                ['key' => VisitsTodayWidget::class, 'visible' => false],
            ]);

        $admin->refresh();

        $this->assertSame(SalesTodayWidget::class, $admin->preference('dashboard_widgets')[0]);
        $this->assertContains(VisitsTodayWidget::class, $admin->preference('dashboard_hidden_widgets'));

        $rendered = Livewire::test(Dashboard::class)->instance()->getWidgets();
        $renderedKeys = array_map(static fn (mixed $widget): string => is_string($widget) ? $widget : $widget->widget, $rendered);

        $this->assertContains(SalesTodayWidget::class, $renderedKeys);
        $this->assertNotContains(VisitsTodayWidget::class, $renderedKeys);
    }

    public function test_dashboard_customization_ignores_unknown_widget_keys(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(Dashboard::class)
            ->callAction('customizeDashboard', [
                'widgets' => [
                    ['key' => 'App\\Filament\\Widgets\\UntrustedWidget', 'visible' => false],
                ],
            ])
            ->assertHasNoActionErrors();

        $admin->refresh();

        $this->assertNotContains('App\\Filament\\Widgets\\UntrustedWidget', $admin->preference('dashboard_widgets'));
        $this->assertNotContains('App\\Filament\\Widgets\\UntrustedWidget', $admin->preference('dashboard_hidden_widgets'));
    }

    public function test_admin_can_reorder_visible_widgets_directly_from_the_dashboard(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->firstOrFail();
        $this->actingAs($admin);

        $dashboard = Livewire::test(Dashboard::class);
        $visibleKeys = array_column($dashboard->instance()->getDashboardWidgetEntries(), 'key');
        $reorderedKeys = array_reverse($visibleKeys);

        $dashboard
            ->assertSee('dashboard-widget-grid')
            ->call('reorderDashboardWidgets', $reorderedKeys)
            ->assertHasNoErrors();

        $admin->refresh();

        $this->assertSame($reorderedKeys, array_slice($admin->preference('dashboard_widgets'), 0, count($reorderedKeys)));
    }

    public function test_dashboard_reorder_rejects_incomplete_or_unknown_widget_keys(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->firstOrFail();
        $this->actingAs($admin);

        $dashboard = Livewire::test(Dashboard::class);
        $visibleKeys = array_column($dashboard->instance()->getDashboardWidgetEntries(), 'key');

        $dashboard
            ->call('reorderDashboardWidgets', [reset($visibleKeys)])
            ->assertHasNoErrors();

        $admin->refresh();

        $this->assertNull($admin->preference('dashboard_widgets'));
    }

    public function test_operational_widgets_expose_authorized_direct_page_links(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->firstOrFail();
        $this->actingAs($admin);

        $dashboard = Livewire::test(Dashboard::class)
            ->assertSee('dashboard-widget-open-link')
            ->assertSeeHtml('role="list"')
            ->assertSeeHtml('role="listitem"');
        $entries = collect($dashboard->instance()->getDashboardWidgetEntries())
            ->keyBy('key');

        $this->assertArrayNotHasKey('lazy', $entries[SalesTodayWidget::class]['properties']);
        $this->assertSame(DailyVisitAssignmentResource::getUrl('index'), $entries[VisitsTodayWidget::class]['url']);
        $this->assertSame(PriceQuotationRequestResource::getUrl('index'), $entries[PendingQuotationsWidget::class]['url']);
        $this->assertSame(AlarmResource::getUrl('index'), $entries[OpenAlarmsWidget::class]['url']);
        $this->assertSame(InvoiceResource::getUrl('index'), $entries[SalesTodayWidget::class]['url']);
        $this->assertSame(InvoiceResource::getUrl('index'), $entries[OutstandingBalanceWidget::class]['url']);
        $this->assertSame(StockResource::getUrl('index'), $entries[LowStockAlertWidget::class]['url']);
        $this->assertSame(UserResource::getUrl('index'), $entries[RepPerformanceWidget::class]['url']);
    }

    public function test_operational_widget_links_are_not_rendered_without_resource_permission(): void
    {
        $restrictedUser = User::where('email', 'admin@jawla.test')->firstOrFail();
        $restrictedUser->syncRoles([]);
        $this->actingAs($restrictedUser);

        $dashboard = Livewire::test(Dashboard::class)
            ->assertDontSee('dashboard-widget-open-link');
        $entries = $dashboard->instance()->getDashboardWidgetEntries();

        $this->assertEmpty(array_filter($entries, fn (array $widget): bool => $widget['url'] !== null));
    }
}
