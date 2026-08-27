<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Services\CallService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property Customer|null $customer
 * @property Collection<int, CustomerContact> $contacts
 */
#[Layout('layouts.app')]
class LogCall extends Component
{
    public int $customerId;

    public ?int $contactId = null;

    public string $direction = 'outbound';

    public int $durationSeconds = 0;

    public string $outcome = 'reached';

    public ?string $notes = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $isRunning = false;

    public ?string $startTime = null;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function startTimer(): void
    {
        $this->isRunning = true;
        $this->startTime = now()->toIso8601String();
        $this->durationSeconds = 0;
    }

    public function stopTimer(): void
    {
        $this->isRunning = false;
        if ($this->startTime) {
            $this->durationSeconds = (int) (now()->diffInSeconds(now()->parse($this->startTime)));
        }
    }

    public function saveCall(): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        $this->validate([
            'customerId' => 'required|exists:customers,id',
            'contactId' => 'nullable|exists:customer_contacts,id',
            'direction' => 'required|in:inbound,outbound',
            'durationSeconds' => 'required|integer|min:1',
            'outcome' => 'required|in:reached,no_answer,busy,left_voicemail',
            'notes' => 'nullable|string',
        ]);

        try {
            app(CallService::class)->create(
                auth()->user()->company_id,
                auth()->id(),
                [
                    'customer_id' => $this->customerId,
                    'contact_id' => $this->contactId,
                    'direction' => $this->direction,
                    'duration_seconds' => $this->durationSeconds,
                    'outcome' => $this->outcome,
                    'notes' => $this->notes,
                ]
            );

            $this->successMessage = __('app.call_logged');
            $this->reset(['contactId', 'direction', 'durationSeconds', 'outcome', 'notes', 'startTime']);
            $this->isRunning = false;
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.call_log_failed');
        }
    }

    public function getCustomerProperty(): ?Customer
    {
        return Customer::find($this->customerId);
    }

    /** @return Collection<int, CustomerContact> */
    public function getContactsProperty(): Collection
    {
        return CustomerContact::where('customer_id', $this->customerId)->get();
    }

    public function render(): View
    {
        return view('livewire.app.log-call', [
            'customer' => $this->customer,
            'contacts' => $this->contacts,
        ]);
    }
}
