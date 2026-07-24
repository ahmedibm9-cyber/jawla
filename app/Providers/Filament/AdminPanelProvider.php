<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use App\Filament\AvatarProviders\CompanyAvatarProvider;
use App\Filament\Pages\CollectPayment;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ReportsPage;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\OpenAlarmsWidget;
use App\Filament\Widgets\OutstandingBalanceWidget;
use App\Filament\Widgets\PendingQuotationsWidget;
use App\Filament\Widgets\RepPerformanceWidget;
use App\Filament\Widgets\SalesTodayWidget;
use App\Filament\Widgets\VisitsTodayWidget;
use App\Http\Middleware\FilamentAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::hex('#6DB83B'),
            ])
            ->font('IBM Plex Sans Arabic')
            ->brandName('Jawla')
            ->brandLogo(asset('images/black-j.webp'))
            ->darkModeBrandLogo(asset('images/white-j.webp'))
            ->brandLogoHeight('4rem')
            ->defaultAvatarProvider(CompanyAvatarProvider::class)
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                ReportsPage::class,
                CollectPayment::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                VisitsTodayWidget::class,
                PendingQuotationsWidget::class,
                OpenAlarmsWidget::class,
                SalesTodayWidget::class,
                OutstandingBalanceWidget::class,
                LowStockAlertWidget::class,
                RepPerformanceWidget::class,
            ])
            ->userMenuItems([
                'locale_en' => MenuItem::make()
                    ->label('English')
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('locale.switch', 'en'))
                    ->visible(fn () => app()->getLocale() !== 'en'),
                'locale_ar' => MenuItem::make()
                    ->label('العربية')
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('locale.switch', 'ar'))
                    ->visible(fn () => app()->getLocale() !== 'ar'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SecurityHeaders::class,
                \App\Http\Middleware\ThrottlePost::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
            ])
            ->renderHook('panels::head.start', fn (): string => '<link rel="preload" href="'.asset('images/black-j.webp').'" as="image" fetchpriority="high">')
            ->renderHook('panels::body.end', fn (): string => '<script>window.areRecordsPartiallySelected??=()=>!1;window.getRecordsOnPage??=()=>[];window.areRecordsSelected??=()=>!1;window.areRecordsToggleable??=()=>!0;</script>');
    }
}
