<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SalesTodayWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $sales = Invoice::where('company_id', $user->company_id)
            ->whereDate('issued_at', today())
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $count = Invoice::where('company_id', $user->company_id)
            ->whereDate('issued_at', today())
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $labels = [
            'ar' => ['مبيعات اليوم', 'فاتورة'],
            'en' => ["Today's Sales", 'invoices'],
        ];

        return [
            Stat::make($labels[$lang][0], number_format((float) $sales, 2).' EGP')
                ->description($count.' '.$labels[$lang][1])
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
