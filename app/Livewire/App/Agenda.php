<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ReturnRecord;
use App\Models\Todo;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property list<array{type: string, title: string, description: string, time: string, status: string, link?: string, priority?: string, amount?: float|string}> $items
 */
#[Layout('layouts.app')]
class Agenda extends Component
{
    public bool $showNonPlannedForm = false;

    public ?int $selectedCustomerId = null;

    public string $visitPurpose = '';

    public string $customerSearch = '';

    /** @var Collection<int, Customer> */
    public $customers = [];

    public function updatedCustomerSearch(): void
    {
        if (strlen($this->customerSearch) < 2) {
            $this->customers = collect();

            return;
        }

        $companyId = auth()->user()->company_id;
        $term = \App\Support\LikeEscape::wrap($this->customerSearch);
        $this->customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('name_ar', 'like', $term)
                ->orWhere('name_en', 'like', $term))
            ->limit(10)
            ->get();
    }

    public function startNonPlannedVisit(): void
    {
        if (! $this->selectedCustomerId || ! auth()->user()) {
            return;
        }

        $customer = Customer::where('company_id', auth()->user()->company_id)
            ->whereKey($this->selectedCustomerId)
            ->first();

        if (! $customer) {
            return;
        }

        Visit::create([
            'company_id' => auth()->user()->activeCompanyId(),
            'user_id' => auth()->id(),
            'customer_id' => $customer->id,
            'route_id' => null,
            'purpose' => $this->visitPurpose ?: 'Non-planned visit',
            'status' => 'in_progress',
            'is_out_of_route' => true,
        ]);

        $this->showNonPlannedForm = false;
        $this->selectedCustomerId = null;
        $this->visitPurpose = '';
        $this->customerSearch = '';
        $this->customers = collect();

        $this->dispatch('visit-created');
    }

    /** @return list<array{type: string, title: string, description: string, time: string, status: string, link?: string, priority?: string, amount?: float|string}> */
    public function getItemsProperty(): array
    {
        $userId = auth()->id();
        $companyId = auth()->user()->company_id;

        $items = [];

        // Today's visits
        $visits = Visit::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->with('customer')
            ->get();

        foreach ($visits as $visit) {
            $items[] = [
                'type' => 'visit',
                'title' => $visit->customer->name_ar ?? $visit->customer->name_en,
                'description' => $visit->purpose,
                'time' => $visit->created_at->format('H:i'),
                'status' => $visit->status,
                'link' => route('app.visit', $visit),
            ];
        }

        // Pending todos
        $todos = Todo::where('user_id', $userId)
            ->where('status', 'new')
            ->whereDate('due_date', '<=', today())
            ->orderBy('due_date')
            ->get();

        foreach ($todos as $todo) {
            $items[] = [
                'type' => 'todo',
                'title' => $todo->title,
                'description' => $todo->description,
                'time' => $todo->due_date->format('H:i'),
                'status' => $todo->status,
                'priority' => $todo->priority,
            ];
        }

        // Recent invoices (today)
        $invoices = Invoice::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->where('status', '!=', 'cancelled')
            ->with('customer')
            ->get();

        foreach ($invoices as $invoice) {
            $items[] = [
                'type' => 'invoice',
                'title' => $invoice->invoice_number,
                'description' => $invoice->customer->name_ar ?? $invoice->customer->name_en,
                'time' => $invoice->created_at->format('H:i'),
                'status' => $invoice->status,
                'amount' => (float) $invoice->total,
            ];
        }

        // Recent returns (today)
        $returns = ReturnRecord::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->with('customer')
            ->get();

        foreach ($returns as $return) {
            $items[] = [
                'type' => 'return',
                'title' => 'Return #'.$return->id,
                'description' => $return->customer->name_ar ?? $return->customer->name_en,
                'time' => $return->created_at->format('H:i'),
                'status' => $return->status,
            ];
        }

        // Sort by time
        usort($items, fn ($a, $b) => strcmp($a['time'], $b['time']));

        return $items;
    }

    public function render(): View
    {
        return view('livewire.app.agenda', [
            'items' => $this->items,
        ]);
    }
}
