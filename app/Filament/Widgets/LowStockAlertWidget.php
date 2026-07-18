<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LowStockAlertWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $user = Auth::user();
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        // Get main warehouse(s) for the company
        $mainWarehouses = Warehouse::where('company_id', $user->company_id)
            ->where('type', 'main')
            ->pluck('id');

        $vanWarehouses = Warehouse::where('company_id', $user->company_id)
            ->where('type', 'van')
            ->pluck('id');

        // Low stock in main warehouse (threshold: 20)
        $mainLowStock = Stock::whereIn('warehouse_id', $mainWarehouses)
            ->where('quantity', '<=', 20)
            ->where('quantity', '>', 0)
            ->count();

        // Out of stock in main warehouse
        $mainOutOfStock = Stock::whereIn('warehouse_id', $mainWarehouses)
            ->where('quantity', '<=', 0)
            ->count();

        // Low stock in van warehouses (threshold: 10)
        $vanLowStock = Stock::whereIn('warehouse_id', $vanWarehouses)
            ->where('quantity', '<=', 10)
            ->where('quantity', '>', 0)
            ->count();

        $vanOutOfStock = Stock::whereIn('warehouse_id', $vanWarehouses)
            ->where('quantity', '<=', 0)
            ->count();

        $totalAlerts = $mainLowStock + $mainOutOfStock + $vanLowStock + $vanOutOfStock;

        $labels = [
            'ar' => ['تنبيهات المخزون', 'منخفض', 'منتهي'],
            'en' => ['Stock Alerts', 'Low', 'Out'],
        ];

        $color = $totalAlerts === 0 ? 'success' : ($mainOutOfStock > 0 || $vanOutOfStock > 0 ? 'danger' : 'warning');

        return [
            Stat::make($labels[$lang][0], $totalAlerts)
                ->description("{$labels[$lang][1]}: " . ($mainLowStock + $vanLowStock) . " · {$labels[$lang][2]}: " . ($mainOutOfStock + $vanOutOfStock))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($color),
        ];
    }
}