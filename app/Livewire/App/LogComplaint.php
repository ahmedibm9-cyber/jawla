<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Services\ComplaintService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LogComplaint extends Component
{
    public ?int $customer_id = null;

    public string $complaint_type = 'other';

    public string $description = '';

    public ?string $successMessage = null;

    public function submit(ComplaintService $service): void
    {
        $validated = $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'complaint_type' => 'required|in:non_conforming_materials,delivery_issue,quality_issue,pricing_issue,other',
            'description' => 'required|string|min:5',
        ]);

        $user = auth()->user();

        $service->log(
            companyId: $user->company_id,
            userId: $user->id,
            customerId: $validated['customer_id'],
            type: $validated['complaint_type'],
            description: $validated['description'],
        );

        $this->reset(['customer_id', 'complaint_type', 'description']);
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم تسجيل الشكوى'
            : 'Complaint logged';
    }

    public function render()
    {
        return view('livewire.app.log-complaint', [
            'customers' => Customer::where('company_id', auth()->user()->company_id)
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->limit(50)
                ->get(),
        ]);
    }
}