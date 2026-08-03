<?php

namespace App\Livewire\App;

use App\Livewire\Concerns\CapturesPhotos;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\CollectionSubmissionService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CollectPayment extends Component
{
    use CapturesPhotos;

    public ?int $customer_id = null;

    public ?int $invoice_id = null;

    public ?float $amount = null;

    public string $method = 'cash';

    public ?string $notes = null;

    public ?string $reference_number = null;

    public bool $success = false;

    public string $successMessage = '';

    public ?int $lastPaymentId = null;

    public array $paymentPrintPayload = [];

    public ?string $printNotice = null;

    public function updatedCustomerId(): void
    {
        $this->invoice_id = null;
        $this->amount = null;
    }

    public function updatedInvoiceId(int|string|null $value): void
    {
        if (! $value) {
            $this->amount = null;

            return;
        }

        $inv = Invoice::find($value);
        if ($inv) {
            $this->amount = (float) $inv->remaining_amount;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:cash,cheque,transfer,other',
            'reference_number' => 'nullable|required_if:method,cheque,transfer|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $submission = app(CollectionSubmissionService::class)->submit(
                rep: auth()->user(),
                customerId: $this->customer_id,
                amount: (float) $this->amount,
                method: $this->method,
                attributes: [
                    'invoice_id' => $this->invoice_id ?: null,
                    'reference_number' => $this->reference_number,
                    'notes' => $this->notes,
                ],
            );
        } catch (\Throwable $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->attachPhotos($submission);
        $this->lastPaymentId = null;
        $this->success = true;
        $this->successMessage = __('app.collection_submitted').' — '.number_format((float) $submission->amount, 2);

        $this->reset(['customer_id', 'invoice_id', 'amount', 'method', 'reference_number', 'notes']);
        $this->method = 'cash';
    }

    /**
     * Offline path: the client has already enqueued the payment to the outbox
     * (IndexedDB) and will sync it when back online. Show the queued confirmation
     * and clear the form — no server write happens here.
     */
    public function queueOffline(): void
    {
        $this->success = true;
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم حفظ الدفعة دون اتصال وستتم مزامنتها تلقائيًا عند عودة الاتصال.'
            : 'Payment saved offline — it will sync automatically when you are back online.';

        $this->reset(['customer_id', 'invoice_id', 'amount', 'method', 'reference_number', 'notes']);
        $this->method = 'cash';
    }

    public function render()
    {
        $user = auth()->user();

        $customers = Customer::query()
            ->where('company_id', $user->activeCompanyId())
            ->where('is_active', true)
            ->where('status', 'approved')
            ->orderBy('name_ar')
            ->limit(100)
            ->get();

        $invoices = [];
        if ($this->customer_id) {
            $invoices = Invoice::query()
                ->where('company_id', auth()->user()->activeCompanyId())
                ->where('customer_id', $this->customer_id)
                ->whereIn('status', ['submitted', 'partially_paid'])
                ->whereRaw('remaining_amount > 0')
                ->orderBy('issued_at', 'desc')
                ->limit(100)
                ->get();
        }

        return view('livewire.app.collect-payment', [
            'customers' => $customers,
            'invoices' => $invoices,
            'user' => $user,
        ]);
    }
}
