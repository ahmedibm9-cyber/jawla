<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CollectionRateWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $user = Auth::user();
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();

        // Total invoiced this month
        $totalInvoiced = Invoice::where('company_id', $user->company_id)
            ->whereBetween('issued_at', [$thisMonthStart, $thisMonthEnd])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('total');

        // Total collected this month
        $totalCollected = Payment::where('company_id', $user->company_id)
            ->whereBetween('collected_at', [$thisMonthStart, $thisMonthEnd])
            ->whereNull('cancelled_at')
            ->sum('amount');

        // Outstanding from this month's invoices
        $outstandingThisMonth = Invoice::where('company_id', $user->company_id)
            ->whereBetween('issued_at', [$thisMonthStart, $thisMonthEnd])
            ->whereNotIn('status', ['cancelled', 'draft', 'paid'])
            ->sum('remaining_amount');

        // Collection rate
        $collectionRate = $totalInvoiced > 0
            ? round(($totalCollected / $totalInvoiced) * 100, 1)
            : 0;

        $labels = [
            'ar' => ['معدل التحصيل', 'مجموع الفواتير', 'مجموع التحصيل', 'مستحق'],
            'en' => ['Collection Rate', 'Total Invoiced', 'Total Collected', 'Outstanding'],
        ];

        $rateColor = $collectionRate >= 90 ? 'success' : ($collectionRate >= 70 ? 'warning' : 'danger');

        return [
            Stat::make($labels[$lang][0], $collectionRate.'%')
                ->description("{$labels[$lang][1]}: ".number_format((float) $totalInvoiced, 2)." · {$labels[$lang][2]}: ".number_format((float) $totalCollected, 2)." · {$labels[$lang][3]}: ".number_format((float) $outstandingThisMonth, 2))
                ->icon('heroicon-o-chart-bar')
                ->color($rateColor),
        ];
    }
}
