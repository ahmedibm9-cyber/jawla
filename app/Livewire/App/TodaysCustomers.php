<?php

namespace App\Livewire\App;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TodaysCustomers extends Component
{
    public string $search = '';

    public function render()
    {
        $customers = Customer::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name_ar', 'ilike', "%{$this->search}%")
                    ->orWhere('name_en', 'ilike', "%{$this->search}%")
                    ->orWhere('phone', 'ilike', "%{$this->search}%")
                    ->orWhere('code', 'ilike', "%{$this->search}%");
            }))
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->limit(30)
            ->get();

        return view('livewire.app.customers', [
            'customers' => $customers,
        ]);
    }
}
