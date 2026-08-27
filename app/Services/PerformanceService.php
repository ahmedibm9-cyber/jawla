<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Request;
use App\Models\ReturnRecord;
use App\Models\Todo;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PerformanceService
{
    public function getOverview(string $period, ?int $userId = null): array
    {
        $startDate = $this->startDate($period);

        $visits = $this->queryVisits($userId, $startDate);
        $invoices = $this->queryInvoices($startDate);
        $returns = $this->queryReturns($startDate);

        return [
            'totalVisits' => $visits['total'],
            'completedVisits' => $visits['completed'],
            'visitAchievement' => $visits['total'] > 0
                ? round(($visits['completed'] / max($visits['total'], 1)) * 100)
                : 0,
            'newCustomers' => Customer::where('company_id', auth()->user()->activeCompanyId())
                ->where('created_at', '>=', $startDate)->count(),
            'totalCustomers' => Customer::where('company_id', auth()->user()->activeCompanyId())
                ->where('is_active', true)->count(),
            'totalInvoices' => $invoices->count(),
            'totalRevenue' => $invoices->where('status', '!=', 'cancelled')->sum('total'),
            'totalReturns' => $returns->count(),
            'totalCalls' => Call::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)->count(),
            'completedTodos' => Todo::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->where('status', 'done')->count(),
            'totalTodos' => Todo::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)->count(),
            'totalRequests' => Request::where('company_id', auth()->user()->activeCompanyId())
                ->where('created_at', '>=', $startDate)->count(),
        ];
    }

    public function getDaily(string $period, ?int $userId = null): Collection
    {
        $startDate = $this->startDate($period);

        return Visit::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', ['completed'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function startDate(string $period): Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };
    }

    private function queryVisits(?int $userId, Carbon $startDate): array
    {
        $query = Visit::where('user_id', $userId)
            ->where('created_at', '>=', $startDate);

        $total = (clone $query)->count();
        $completed = (clone $query)->where('status', 'completed')->count();

        return ['total' => $total, 'completed' => $completed];
    }

    private function queryInvoices(Carbon $startDate): Collection
    {
        return Invoice::where('company_id', auth()->user()->activeCompanyId())
            ->where('created_at', '>=', $startDate)
            ->get();
    }

    private function queryReturns(Carbon $startDate): Collection
    {
        return ReturnRecord::where('company_id', auth()->user()->activeCompanyId())
            ->where('created_at', '>=', $startDate)
            ->get();
    }
}
