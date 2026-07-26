<?php

namespace App\Livewire\App;

use App\Livewire\Concerns\CapturesPhotos;
use App\Models\Customer;
use App\Services\ComplaintService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LogComplaint extends Component
{
    use CapturesPhotos;

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

        $complaint = $service->log(
            companyId: $user->activeCompanyId(),
            userId: $user->id,
            customerId: $validated['customer_id'],
            type: $validated['complaint_type'],
            description: $validated['description'],
        );

        $this->attachPhotos($complaint);

        $this->reset(['customer_id', 'complaint_type', 'description']);
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم تسجيل الشكوى'
            : 'Complaint logged';
    }

    /**
     * Offline path: the client has already enqueued the complaint to the outbox
     * (IndexedDB) and will sync it when back online. Show the queued confirmation
     * and clear the form — no server write happens here.
     */
    public function queueOffline(): void
    {
        $this->reset(['customer_id', 'complaint_type', 'description']);
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم حفظ الشكوى دون اتصال وستتم مزامنتها تلقائيًا عند عودة الاتصال.'
            : 'Complaint saved offline — it will sync automatically when you are back online.';
    }

    public function render()
    {
        return view('livewire.app.log-complaint', [
            'customers' => Customer::where('company_id', auth()->user()->activeCompanyId())
                ->where('is_active', true)
                ->where('status', 'approved')
                ->orderBy('name_ar')
                ->limit(50)
                ->get(),
        ]);
    }
}
