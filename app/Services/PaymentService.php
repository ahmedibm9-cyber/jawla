<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Contracts\PaymentService as PaymentServiceContract;
use Illuminate\Support\Facades\DB;

class PaymentService implements PaymentServiceContract
{
    public function collect(int $companyId, int $userId, int $customerId, float $amount, string $method, ?int $invoiceId = null, ?int $visitId = null, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($companyId, $userId, $customerId, $amount, $method, $invoiceId, $visitId, $notes): Payment {
            if ($amount <= 0) {
                throw new DomainException('Payment amount must be greater than zero.');
            }

            $customer = Customer::whereKey($customerId)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();
            if (! $customer) {
                throw new DomainException('errors.resource.customer');
            }

            $invoice = null;
            if ($invoiceId) {
                $invoice = Invoice::whereKey($invoiceId)
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();
                if (! $invoice || $invoice->customer_id !== $customerId) {
                    throw new DomainException('errors.resource.invoice');
                }
                if (bccomp(number_format($amount, 2, '.', ''), (string) $invoice->remaining_amount, 2) > 0) {
                    throw new DomainException('Payment exceeds the invoice balance.');
                }
            }

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

            if ($invoice) {
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

            $customer->decrement('balance', $amount);

            return $payment;
        });
    }

    public function cancel(Payment $payment, int $userId, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $userId, $reason): Payment {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->cancelled_at !== null) {
                return $payment;
            }

            $cashBox = $this->ensureCashBox($payment->user_id, $payment->company_id);

            if ($payment->method === 'cash') {
                $cashBox->decrement('balance', (float) $payment->amount);
            }

            if ($payment->invoice_id) {
                $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
                $invoice->decrement('paid_amount', (float) $payment->amount);
                $invoice->increment('remaining_amount', (float) $payment->amount);

                if ((float) $invoice->remaining_amount > 0 && $invoice->status === InvoiceStatus::Paid) {
                    $invoice->update(['status' => InvoiceStatus::PartiallyPaid]);
                }
            }

            Customer::whereKey($payment->customer_id)
                ->where('company_id', $payment->company_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('balance', (float) $payment->amount);

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
        // A cash box is unique per user. Lock the user row before looking it up
        // so concurrent first collections cannot create competing cash boxes.
        $user = User::withoutGlobalScopes()->whereKey($userId)->lockForUpdate()->firstOrFail();
        if ($user->company_id !== $companyId) {
            throw new DomainException('Cash box user does not belong to this company.');
        }

        // Legacy cash boxes predate company_id, so this lookup must include the
        // nullable rows that the company global scope would otherwise hide.
        $cashBox = CashBox::withoutGlobalScopes()->where('user_id', $userId)->lockForUpdate()->first();
        if (! $cashBox) {
            $cashBox = CashBox::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'balance' => 0,
            ]);
        }

        if ($cashBox->company_id === null) {
            $cashBox->update(['company_id' => $companyId]);
        }

        return $cashBox->refresh();
    }
}
