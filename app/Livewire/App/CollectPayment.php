<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CollectPayment extends Component
{
    public ?int $customer_id = null;

    public ?int $invoice_id = null;

    public ?float $amount = null;

    public string $method = 'cash';

    public ?string $notes = null;

    public bool $success = false;

    public string $successMessage = '';

    public ?int $lastPaymentId = null;

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
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:cash,cheque,transfer,other',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = app(PaymentService::class)->collect(
            companyId: auth()->user()->company_id,
            userId: auth()->id(),
            customerId: $this->customer_id,
            amount: (float) $this->amount,
            method: $this->method,
            invoiceId: $this->invoice_id ?: null,
            notes: $this->notes ?: null,
        );

        $this->lastPaymentId = $payment->id;
        $this->success = true;
        $this->successMessage = __('app.payment_collected') . ' — ' . number_format((float) $payment->amount, 2);

        $this->reset(['customer_id', 'invoice_id', 'amount', 'method', 'notes']);
        $this->method = 'cash';
    }

    public function render()
    {
        $user = auth()->user();

        $customers = Customer::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        $invoices = [];
        if ($this->customer_id) {
            $invoices = Invoice::query()
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
