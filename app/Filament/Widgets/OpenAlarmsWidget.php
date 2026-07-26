<?php

namespace App\Filament\Widgets;

use App\Models\Alarm;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OpenAlarmsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $critical = Alarm::where('company_id', $user->activeCompanyId())
            ->where('severity', 'critical')
            ->where('is_read', false)
            ->count();

        $unack = Alarm::where('company_id', $user->activeCompanyId())
            ->where('is_read', false)
            ->count();

        $labels = [
            'ar' => ['تنبيهات غير مقروءة', 'حرجة'],
            'en' => ['Unread Alarms', 'Critical'],
        ];

        return [
            Stat::make($labels[$lang][0], $unack)
                ->description($labels[$lang][1].': '.$critical)
                ->icon('heroicon-o-bell-alert')
                ->color($critical > 0 ? 'danger' : ($unack > 0 ? 'warning' : 'success')),
        ];
    }
}
