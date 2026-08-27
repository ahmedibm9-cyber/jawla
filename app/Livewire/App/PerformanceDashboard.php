<?php

namespace App\Livewire\App;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ReturnRecord;
use App\Models\Todo;
use App\Models\Visit;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property array{totalVisits: int, completedVisits: int, visitAchievement: float|int, newCustomers: int, totalCustomers: int, totalInvoices: int, totalRevenue: float|int|numeric-string, totalReturns: int, totalCalls: int, completedTodos: int, totalTodos: int} $metrics
 */
#[Layout('layouts.app')]
class PerformanceDashboard extends Component
{
    public string $period = 'today';

    public function updatedPeriod(): void
    {
        $this->dispatch('refresh');
    }

    /** @return array{totalVisits: int, completedVisits: int, visitAchievement: float|int, newCustomers: int, totalCustomers: int, totalInvoices: int, totalRevenue: float|int|numeric-string, totalReturns: int, totalCalls: int, completedTodos: int, totalTodos: int} */
    public function getMetricsProperty(): array
    {
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;

        $startDate = match ($this->period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        // Visit metrics
        $totalVisits = Visit::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $completedVisits = Visit::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->count();

        // Customer metrics
        $newCustomers = Customer::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalCustomers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        // Invoice metrics
        $totalInvoices = Invoice::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalRevenue = Invoice::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        // Return metrics
        $totalReturns = ReturnRecord::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Call metrics
        $totalCalls = Call::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Todo metrics
        $completedTodos = Todo::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->where('status', 'done')
            ->count();

        $totalTodos = Todo::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Achievement percentage
        $visitAchievement = $totalVisits > 0
            ? round(($completedVisits / max($totalVisits, 1)) * 100)
            : 0;

        return [
            'totalVisits' => $totalVisits,
            'completedVisits' => $completedVisits,
            'visitAchievement' => $visitAchievement,
            'newCustomers' => $newCustomers,
            'totalCustomers' => $totalCustomers,
            'totalInvoices' => $totalInvoices,
            'totalRevenue' => $totalRevenue,
            'totalReturns' => $totalReturns,
            'totalCalls' => $totalCalls,
            'completedTodos' => $completedTodos,
            'totalTodos' => $totalTodos,
        ];
    }

    public function render(): View
    {
        return view('livewire.app.performance-dashboard', [
            'metrics' => $this->metrics,
        ]);
    }
}
