<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CollectPayment;
use App\Filament\Pages\ReportsPage;
use App\Filament\Widgets\OpenAlarmsWidget;
use App\Filament\Widgets\PendingQuotationsWidget;
use App\Filament\Widgets\SalesTodayWidget;
use App\Filament\Widgets\VisitsTodayWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
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
            ->login()
            ->colors([
                'primary' => Color::hex('#4DB848'),
            ])
            ->font('IBM Plex Sans Arabic')
            ->brandName('Jawla')
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
                FilamentInfoWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
