<?php

namespace App\Filament\Widgets;

use App\Models\DailyVisitAssignment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\Auth;

class VisitsTodayWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $assigned = DailyVisitAssignment::where('company_id', $companyId)
            ->whereDate('visit_date', today())
            ->count();

        $completed = DailyVisitAssignment::where('company_id', $companyId)
            ->whereDate('visit_date', today())
            ->where('status', 'completed')
            ->count();

        $pending = DailyVisitAssignment::where('company_id', $companyId)
            ->whereDate('visit_date', today())
            ->where('status', 'pending')
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
    }
}