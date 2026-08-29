<?php

namespace App\Livewire\App;

use App\Models\Customer;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TodaysCustomers extends Component
{
    use WithPagination;

    public string $search = '';

    public int $expandedCustomerId = 0;

    public function toggleCustomerActions(int $customerId): void
    {
        $this->expandedCustomerId = $this->expandedCustomerId === $customerId ? 0 : $customerId;
    }

    public function render(): View
    {
        $customers = Customer::query()
            ->where('company_id', auth()->user()->activeCompanyId())
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $term = \App\Support\LikeEscape::wrap($this->search);
                $q->where('name_ar', 'ilike', $term)
                    ->orWhere('name_en', 'ilike', $term)
                    ->orWhere('phone', 'ilike', $term)
                    ->orWhere('code', 'ilike', $term);
            }))
            ->where('is_active', true)
            ->withCount(['invoices', 'visits'])
            ->orderBy('name_ar')
            ->paginate(30);

        return view('livewire.app.customers', [
            'customers' => $customers,
        ]);
    }
}
