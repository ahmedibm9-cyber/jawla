<?php

namespace App\Filament\Widgets;

use App\Models\PriceQuotationRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PendingQuotationsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $pending = PriceQuotationRequest::where('company_id', $user->company_id)
            ->where('status', 'requested')
            ->count();

        $priced = PriceQuotationRequest::where('company_id', $user->company_id)
            ->where('status', 'priced')
            ->count();

        $labels = [
            'ar' => ['عروض الأسعار قيد الانتظار', 'بانتظار التسعير'],
            'en' => ['Pending Quotation Requests', 'Awaiting Pricing'],
        ];

        return [
            Stat::make($labels[$lang][0], $pending)
                ->description($labels[$lang][1].': '.$priced)
                ->icon('heroicon-o-currency-dollar')
                ->color($pending > 3 ? 'danger' : 'warning'),
        ];
    }
}
