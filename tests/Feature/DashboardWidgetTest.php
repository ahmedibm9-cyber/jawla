<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
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
            ->callAction('customizeDashboard', [
                'widgets' => [
                    ['key' => SalesTodayWidget::class, 'visible' => true],
                    ['key' => VisitsTodayWidget::class, 'visible' => false],
                ],
            ])
            ->assertHasNoActionErrors();

        $admin->refresh();

        $this->assertSame(SalesTodayWidget::class, $admin->preference('dashboard_widgets')[0]);
        $this->assertContains(VisitsTodayWidget::class, $admin->preference('dashboard_hidden_widgets'));

        $rendered = Livewire::test(Dashboard::class)->instance()->getWidgets();
        $renderedKeys = array_map(static fn (mixed $widget): string => is_string($widget) ? $widget : $widget->widget, $rendered);

        $this->assertSame(SalesTodayWidget::class, $renderedKeys[0]);
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
}
