<?php

namespace App\Filament\Widgets;

use App\Services\RoiService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RepRoiWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 9;

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        try {
            $roi = app(RoiService::class)->repRoi($user->id, now()->startOfMonth(), now());

            $labels = [
                'ar' => ['أداء هذا الشهر', 'زيارة مكتملة', 'نسبة الإتمام', 'المبيعات', 'التحصيل'],
                'en' => ['Monthly ROI', 'Visits Done', 'Completion', 'Sales', 'Collected'],
            ];

            return [
                Stat::make($labels[$lang][1], "{$roi['completed']}/{$roi['assigned']}")
                    ->description("{$labels[$lang][2]}: {$roi['completion_rate']}%")
                    ->icon('heroicon-o-calendar-check')
                    ->color('primary'),
                Stat::make($labels[$lang][3], number_format($roi['sales'], 2))
                    ->description("{$labels[$lang][4]}: {$roi['collection_rate']}%")
                    ->icon('heroicon-o-banknotes')
                    ->color($roi['collection_rate'] >= 80 ? 'success' : 'warning'),
            ];
        } catch (\Throwable $e) {
            return [
                Stat::make($lang === 'ar' ? 'الأداء' : 'ROI', '—')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray'),
            ];
        }
    }
}
