<?php

namespace App\Filament\Widgets;

use App\Models\DailyVisitAssignment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class VisitsTodayWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $companyId = $user->activeCompanyId();
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        try {
            $assigned = DailyVisitAssignment::where('company_id', $companyId)
                ->whereDate('visit_date', today())
                ->count();

            $completed = DailyVisitAssignment::where('company_id', $companyId)
                ->whereDate('visit_date', today())
                ->where('status', 'completed')
                ->count();

            $pending = DailyVisitAssignment::where('company_id', $companyId)
                ->whereDate('visit_date', today())
                ->where('status', 'approved')
                ->count();

            $labels = [
                'ar' => ['الزيارات اليوم', 'مكتملة', 'معلقة'],
                'en' => ["Today's Visits", 'Completed', 'Pending'],
            ];

            return [
                Stat::make($labels[$lang][0], $assigned)
                    ->description($labels[$lang][1].': '.$completed.' · '.$labels[$lang][2].': '.$pending)
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary'),
            ];
        } catch (\Throwable $e) {
            return [
                Stat::make($labels[$lang][0], '—')
                    ->description(__('widgets.loading_error'))
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray'),
            ];
        }
    }
}
