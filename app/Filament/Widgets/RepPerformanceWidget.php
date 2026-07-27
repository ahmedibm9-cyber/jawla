<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RepPerformanceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 8;

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        // Only show for managers/admins
        if (! $user->can('reports.view')) {
            return [
                Stat::make(
                    $lang === 'ar' ? 'أداء المندوبين' : 'Rep Performance',
                    '—',
                )
                    ->icon('heroicon-o-users')
                    ->color('gray'),
            ];
        }

        $today = today();

        try {
            // Get reps in company
            $reps = User::forCompany($user->activeCompanyId())
                ->whereHas('roles', fn ($q) => $q->where('name', 'rep'))
                ->withCount(['visits as visits_today' => fn ($q) => $q->whereDate('checkin_at', $today)])
                ->withSum('invoices as sales_today', fn ($q) => $q->whereDate('issued_at', $today)->whereNotIn('status', ['cancelled']))
                ->get();

            $totalVisits = $reps->sum('visits_today');
            $totalSales = $reps->sum('sales_today');
            $activeReps = $reps->where('visits_today', '>', 0)->count();
            $totalReps = $reps->count();

            $labels = [
                'ar' => ['أداء المندوبين', 'زيارات اليوم', 'مبيعات اليوم', 'مندوبين نشطين'],
                'en' => ['Rep Performance', "Today's Visits", "Today's Sales", 'Active Reps'],
            ];

            return [
                Stat::make($labels[$lang][1], $totalVisits)
                    ->description("{$labels[$lang][3]}: {$activeReps}/{$totalReps} · {$labels[$lang][2]}: ".number_format((float) $totalSales, 2))
                    ->icon('heroicon-o-users')
                    ->color('primary'),
            ];
        } catch (\Throwable $e) {
            $labels = [
                'ar' => ['أداء المندوبين', 'خطأ'],
                'en' => ['Rep Performance', 'Error'],
            ];

            return [
                Stat::make($labels[$lang][0], '—')
                    ->icon('heroicon-o-users')
                    ->color('gray'),
            ];
        }
    }
}
