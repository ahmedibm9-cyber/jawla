<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CustomerSummaryReport extends Component
{
    public string $sortBy = 'name';
    public string $sortDir = 'asc';
    public string $statusFilter = 'all';

    public function toggleSort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'asc';
        }
    }

    /** @return array{metrics: array, customers: Collection} */
    public function getDataProperty(): array
    {
        $companyId = auth()->user()->company_id;

        $totalCustomers = Customer::where('company_id', $companyId)->count();
        $activeCustomers = Customer::where('company_id', $companyId)->where('is_active', true)->count();
        $inactiveCustomers = $totalCustomers - $activeCustomers;

        $newThisMonth = Customer::where('company_id', $companyId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $overdueCustomers = Customer::where('company_id', $companyId)
            ->where('balance', '>', 0)
            ->where('is_active', true)
            ->count();

        $customers = Customer::where('company_id', $companyId)
            ->withCount(['invoices', 'visits'])
            ->withSum('invoices as total_order_value', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->orderBy($this->sortBy, $this->sortDir)
            ->get();

        return [
            'metrics' => [
                'total' => $totalCustomers,
                'active' => $activeCustomers,
                'inactive' => $inactiveCustomers,
                'newThisMonth' => $newThisMonth,
                'overdue' => $overdueCustomers,
            ],
            'customers' => $customers,
        ];
    }

    public function exportCsv(): void
    {
        $data = $this->data;
        $customers = $data['customers'];

        $headers = ['Name', 'Phone', 'Code', 'Status', 'Balance', 'Total Orders', 'Total Visits'];
        $rows = $customers->map(fn ($c) => [
            l($c->name_ar, $c->name_en ?? $c->name_ar),
            $c->phone,
            $c->code,
            $c->is_active ? 'Active' : 'Inactive',
            number_format((float) $c->balance, 2),
            $c->invoices_count,
            $c->visits_count,
        ]);

        $csv = implode("\n", array_merge(
            [implode(",", $headers)],
            $rows->map(fn ($r) => implode(",", array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $r)))->toArray()
        ));

        response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="customers_' . now()->format('Y-m-d') . '.csv"')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.app.customer-summary-report', [
            'data' => $this->data,
        ]);
    }
}
