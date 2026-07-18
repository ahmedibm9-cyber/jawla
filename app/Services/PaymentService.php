<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\CashBox;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function collect(int $companyId, int $userId, int $customerId, float $amount, string $method, ?int $invoiceId = null, ?int $visitId = null, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($companyId, $userId, $customerId, $amount, $method, $invoiceId, $visitId, $notes): Payment {
            $payment = Payment::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'user_id' => $userId,
                'invoice_id' => $invoiceId,
                'visit_id' => $visitId,
                'amount' => $amount,
                'method' => $method,
                'collected_at' => now(),
                'posting_date' => today(),
                'notes' => $notes,
            ]);

            $cashBox = $this->ensureCashBox($userId, $companyId);

            if ($method === 'cash') {
                $cashBox->increment('balance', $amount);
            }

            if ($invoiceId) {
                $invoice = Invoice::findOrFail($invoiceId);
                $invoice->increment('paid_amount', $amount);
                $invoice->decrement('remaining_amount', $amount);

                // Read fresh values from DB to avoid stale state
                $invoice->refresh();

                if ((float) $invoice->remaining_amount <= 0) {
                    $invoice->update(['status' => InvoiceStatus::Paid]);
                } elseif ((float) $invoice->paid_amount > 0 && $invoice->status !== InvoiceStatus::Paid) {
                    $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);
                }
            }

            $customer = $payment->customer;
            if ((float) $customer->balance > 0) {
                $customer->decrement('balance', $amount);
            }

            return $payment;
        });
    }

    public function cancel(Payment $payment, int $userId, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $userId, $reason): Payment {
            $cashBox = $this->ensureCashBox($payment->user_id, $payment->company_id);

            if ($payment->method === 'cash') {
                $cashBox->decrement('balance', (float) $payment->amount);
            }

            if ($payment->invoice_id) {
                $invoice = Invoice::findOrFail($payment->invoice_id);
                $invoice->decrement('paid_amount', (float) $payment->amount);
                $invoice->increment('remaining_amount', (float) $payment->amount);

                if ((float) $invoice->remaining_amount > 0 && $invoice->status === InvoiceStatus::Paid) {
                    $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);
                }
            }

            $payment->customer->increment('balance', (float) $payment->amount);

            $payment->update([
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'notes' => ($payment->notes ? $payment->notes."\n" : '')."Cancelled: {$reason}",
            ]);

            return $payment;
        });
    }

    private function ensureCashBox(int $userId, int $companyId): CashBox
    {
        $cashBox = CashBox::where('user_id', $userId)->first();
        if (! $cashBox) {
            $cashBox = CashBox::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'balance' => 0,
            ]);
        }

        if (! $cashBox->company_id) {
            $cashBox->update(['company_id' => $companyId]);
        }

        return $cashBox->refresh();
    }
}
