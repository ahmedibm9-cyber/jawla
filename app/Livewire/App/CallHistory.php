<?php

namespace App\Livewire\App;

use App\Models\Call;
use App\Models\Customer;
use App\Services\CallService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Customer|null $customer
 * @property Collection<int, Call> $calls
 */
#[Layout('layouts.app')]
class CallHistory extends Component
{
    public int $customerId;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomerProperty(): ?Customer
    {
        return Customer::find($this->customerId);
    }

    /** @return Collection<int, Call> */
    public function getCallsProperty(): Collection
    {
        return app(CallService::class)->getForCustomer($this->customerId);
    }

    public function render(): View
    {
        return view('livewire.app.call-history', [
            'customer' => $this->customer,
            'calls' => $this->calls,
        ]);
    }
}
