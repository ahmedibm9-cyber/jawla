<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OutstandingBalanceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $user = Auth::user();
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $outstanding = Invoice::where('company_id', $user->company_id)
            ->whereIn('status', ['submitted', 'partially_paid'])
            ->sum('remaining_amount');

        $overdueCount = Invoice::where('company_id', $user->company_id)
            ->whereIn('status', ['submitted', 'partially_paid'])
            ->whereDate('issued_at', '<', now()->subDays(30))
            ->count();

        $labels = [
            'ar' => ['الرصيد المستحق', 'متأخر > 30 يوم'],
            'en' => ['Outstanding Balance', 'Overdue > 30 days'],
        ];

        return [
            Stat::make($labels[$lang][0], number_format((float) $outstanding, 2) . ' EGP')
                ->description($labels[$lang][1] . ': ' . $overdueCount)
                ->icon('heroicon-o-currency-dollar')
                ->color($outstanding > 0 ? 'warning' : 'success'),
        ];
    }
}