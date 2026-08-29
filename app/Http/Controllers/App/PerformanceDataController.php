<?php

namespace App\Http\Controllers\App;

use App\Models\Invoice;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerformanceDataController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $companyId = $request->user()->activeCompanyId();
        $period = $request->query('period', 'today');

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $endDate = now()->endOfDay();
        $labels = [];
        $visits = [];
        $revenue = [];

        $current = $startDate->copy()->startOfDay();
        while ($current->lte($endDate)) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $labels[] = $current->format('M d');

            $visits[] = Visit::where('user_id', $userId)
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<=', $dayEnd)
                ->count();

            $revenue[] = (float) Invoice::where('company_id', $companyId)
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<=', $dayEnd)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            $current->addDay();
        }

        return response()->json([
            'labels' => $labels,
            'visits' => $visits,
            'revenue' => $revenue,
        ]);
    }
}
