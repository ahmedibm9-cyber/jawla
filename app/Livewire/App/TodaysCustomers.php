<?php

namespace App\Livewire\App;

use App\Models\Customer;
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

    public function render()
    {
        $customers = Customer::query()
            ->where('company_id', auth()->user()->activeCompanyId())
            ->when($this->search, function ($q) {
                $escaped = addcslashes(trim($this->search), '%_');
                $q->where(function ($q) use ($escaped) {
                    $q->where('name_ar', 'ilike', "%{$escaped}%")
                        ->orWhere('name_en', 'ilike', "%{$escaped}%")
                        ->orWhere('phone', 'ilike', "%{$escaped}%")
                        ->orWhere('code', 'ilike', "%{$escaped}%");
                });
            })
            ->where('is_active', true)
            ->withCount(['invoices', 'visits'])
            ->orderBy('name_ar')
            ->paginate(30);

        return view('livewire.app.customers', [
            'customers' => $customers,
        ]);
    }
}
