<?php

namespace App\Livewire\App;

use App\Livewire\Concerns\CapturesPhotos;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ReturnRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LogReturn extends Component
{
    use CapturesPhotos;

    public ?int $customer_id = null;

    public ?int $against_invoice_id = null;

    public string $reason = '';

    /** @var list<array{invoice_item_id: string|int, quantity: int|float, condition: string}> */
    public array $items = [];

    public bool $success = false;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = ['invoice_item_id' => '', 'quantity' => 1, 'condition' => 'sellable'];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function submit(): void
    {
        $this->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'against_invoice_id' => 'required|integer|exists:invoices,id',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'required|integer|exists:invoice_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001|max:9999',
            'items.*.condition' => 'required|in:sellable,damaged',
        ]);

        try {
            $return = app(ReturnRequestService::class)->submit(
                rep: auth()->user(),
                customerId: $this->customer_id,
                invoiceId: $this->against_invoice_id,
                items: array_map(fn (array $item) => [
                    'invoice_item_id' => (int) $item['invoice_item_id'],
                    'quantity' => (float) $item['quantity'],
                    'condition' => $item['condition'],
                ], $this->items),
                reason: $this->reason,
            );

            $this->attachPhotos($return);
            $this->success = true;
            $this->successMessage = __('app.return_request_submitted').' — '.$return->request_number;
            $this->resetForm();
        } catch (\DomainException|AuthorizationException $exception) {
            $this->errorMessage = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.workflow_failed');
        }
    }

    public function queueOffline(): void
    {
        $this->success = true;
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم حفظ المرتجع دون اتصال وستتم مزامنته تلقائياً عند عودة الاتصال.'
            : 'Return saved offline — it will sync automatically when you are back online.';
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['customer_id', 'against_invoice_id', 'reason', 'items']);
        $this->addItem();
    }

    public function render(): View
    {
        $user = auth()->user();
        $companyId = $user->activeCompanyId();
        $customers = Customer::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->orderBy('name_ar')
            ->limit(100)
            ->get();
        $invoices = Invoice::query()
            ->where('company_id', $companyId)
            ->when($this->customer_id, fn ($query) => $query->where('customer_id', $this->customer_id))
            ->whereIn('status', ['issued', 'submitted', 'partially_paid', 'paid'])
            ->latest('issued_at')
            ->limit(100)
            ->get();
        $invoiceLines = $this->against_invoice_id
            ? InvoiceItem::query()
                ->where('invoice_id', $this->against_invoice_id)
                ->with('product:id,name_ar,name_en')
                ->limit(200)
                ->get()
            : collect();

        return view('livewire.app.log-return', compact('customers', 'invoices', 'invoiceLines'));
    }
}
