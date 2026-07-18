<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ReversalService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments,
        private readonly ExpenseService $expenses,
    ) {}

    public function reverseInvoice(Invoice $invoice, string $reason): Invoice
    {
        $result = DB::transaction(function () use ($invoice, $reason): Invoice {
            $cancelled = $this->invoices->cancel($invoice, auth()->id(), $reason);

            Activity::log('invoice_reversed', $cancelled, 'Invoice '.$cancelled->invoice_number.' reversed: '.$reason);

            return $cancelled;
        });

        return $result;
    }

    public function reversePayment(Payment $payment, string $reason): Payment
    {
        $result = DB::transaction(function () use ($payment, $reason): Payment {
            $cancelled = $this->payments->cancel($payment, auth()->id(), $reason);

            Activity::log('payment_reversed', $cancelled, 'Payment #'.$cancelled->id.' reversed: '.$reason);

            return $cancelled;
        });

        return $result;
    }
}
