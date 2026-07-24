<?php

namespace Tests\Feature;

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
}
