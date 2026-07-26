<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reversal;
use App\Models\User;
use App\Services\Contracts\PaymentService as PaymentServiceContract;
use App\Support\ActiveCompanyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PaymentService implements PaymentServiceContract
{
    public function collect(
        int $companyId,
        int $userId,
        int $customerId,
        float $amount,
        string $method,
        ?int $invoiceId = null,
        ?int $visitId = null,
        ?string $notes = null,
        ?string $intentId = null,
    ): Payment {
        app(ActiveCompanyContext::class)->assertMatches($companyId);
        $money = number_format($amount, 2, '.', '');
        if (bccomp($money, '0.00', 2) <= 0) {
            throw new DomainException('Payment amount must be greater than zero.');
        }

        try {
            return DB::transaction(function () use (
                $companyId,
                $userId,
                $customerId,
                $money,
                $method,
                $invoiceId,
                $visitId,
                $notes,
                $intentId,
            ): Payment {
                $collector = User::withoutGlobalScopes()->whereKey($userId)->lockForUpdate()->firstOrFail();
                if (! $collector->hasCompanyAccess($companyId)
                    || ! $collector->hasAnyRole(['sales_rep', 'sales_manager'])) {
                    throw new DomainException('Only an assigned sales user may collect a payment.');
                }
                if ($intentId !== null) {
                    $existing = Payment::where('company_id', $companyId)
                        ->where('intent_id', $intentId)
                        ->lockForUpdate()
                        ->first();
                    if ($existing) {
                        $this->assertIntentMatches(
                            $existing, $userId, $customerId, $invoiceId, $money, $method,
                        );

                        return $existing;
                    }
                }

                $customer = Customer::whereKey($customerId)
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();
                if (! $customer) {
                    throw new DomainException('errors.resource.customer');
                }

                $invoice = null;
                $allocated = bccomp($money, (string) $customer->balance, 2) > 0
                    ? (string) $customer->balance
                    : $money;
                if ($invoiceId !== null) {
                    $invoice = Invoice::whereKey($invoiceId)
                        ->where('company_id', $companyId)
                        ->lockForUpdate()
                        ->first();
                    if (! $invoice || (int) $invoice->customer_id !== $customerId) {
                        throw new DomainException('errors.resource.invoice');
                    }
                    if (! in_array($invoice->status, [
                        InvoiceStatus::Issued,
                        InvoiceStatus::Submitted,
                        InvoiceStatus::PartiallyPaid,
                    ], true)) {
                        throw new DomainException('Payments cannot be posted to a terminal or draft invoice.');
                    }
                    $allocated = bccomp($money, (string) $invoice->remaining_amount, 2) > 0
                        ? (string) $invoice->remaining_amount
                        : $money;
                }
                $unallocated = bcsub($money, $allocated, 2);

                $payment = Payment::create([
                    'company_id' => $companyId,
                    'customer_id' => $customerId,
                    'user_id' => $userId,
                    'invoice_id' => $invoiceId,
                    'visit_id' => $visitId,
                    'amount' => $money,
                    'allocated_amount' => $allocated,
                    'unallocated_amount' => $unallocated,
                    'intent_id' => $intentId,
                    'method' => $method,
                    'collected_at' => now(),
                    'posting_date' => today(),
                    'notes' => $notes,
                ]);

                $cashBox = $this->ensureCashBox($userId, $companyId);
                if ($method === 'cash') {
                    $cashBox->balance = bcadd((string) $cashBox->balance, $money, 2);
                    $cashBox->save();
                }

                if ($invoice) {
                    $invoice->paid_amount = bcadd((string) $invoice->paid_amount, $allocated, 2);
                    $invoice->remaining_amount = bcsub((string) $invoice->remaining_amount, $allocated, 2);
                    $invoice->status = bccomp((string) $invoice->remaining_amount, '0.00', 2) === 0
                        ? InvoiceStatus::Paid
                        : InvoiceStatus::PartiallyPaid;
                    $invoice->save();
                }
                if (bccomp($allocated, '0.00', 2) > 0) {
                    $customer->balance = bcsub((string) $customer->balance, $allocated, 2);
                    $customer->save();
                }
                if (bccomp($unallocated, '0.00', 2) > 0) {
                    CustomerCredit::create([
                        'company_id' => $companyId,
                        'customer_id' => $customerId,
                        'invoice_id' => $invoiceId,
                        'payment_id' => $payment->id,
                        'created_by' => $userId,
                        'credit_number' => 'CREDIT-PAY-'.$payment->id,
                        'amount' => $unallocated,
                        'remaining_amount' => $unallocated,
                        'status' => 'available',
                        'reason' => 'Unallocated payment overage',
                    ]);
                }

                return $payment;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if ($intentId === null) {
                throw $exception;
            }
            $existing = Payment::where('company_id', $companyId)
                ->where('intent_id', $intentId)
                ->first();
            if (! $existing) {
                throw $exception;
            }
            $this->assertIntentMatches(
                $existing, $userId, $customerId, $invoiceId, $money, $method,
            );

            return $existing;
        }
    }

    public function cancel(Payment $payment, int $userId, string $reason): Payment
    {
        $manager = User::withoutGlobalScopes()->findOrFail($userId);
        if (! $manager->hasRole('sales_manager') || trim($reason) === '') {
            throw new DomainException('A sales manager and mandatory reason are required for payment reversal.');
        }

        return DB::transaction(function () use ($payment, $userId, $reason): Payment {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $existingReversal = Reversal::where('original_type', Payment::class)
                ->where('original_id', $payment->id)
                ->where('action', 'reverse')
                ->lockForUpdate()
                ->first();
            if ($existingReversal) {
                return $payment;
            }
            if ($payment->cancelled_at !== null) {
                return $payment;
            }
            $credit = CustomerCredit::where('payment_id', $payment->id)->lockForUpdate()->first();
            if ($credit && bccomp((string) $credit->remaining_amount, (string) $credit->amount, 2) !== 0) {
                throw new DomainException('Payment credit has dependent usage and cannot be reversed.');
            }

            $cashBox = $this->ensureCashBox($payment->user_id, $payment->company_id);
            if ($payment->method === 'cash') {
                if (bccomp((string) $cashBox->balance, (string) $payment->amount, 2) < 0) {
                    throw new DomainException('Cash-box balance is insufficient for this reversal.');
                }
                $cashBox->balance = bcsub((string) $cashBox->balance, (string) $payment->amount, 2);
                $cashBox->save();
            }
            if ($payment->invoice_id) {
                $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
                $invoice->paid_amount = bcsub((string) $invoice->paid_amount, (string) $payment->allocated_amount, 2);
                $invoice->remaining_amount = bcadd((string) $invoice->remaining_amount, (string) $payment->allocated_amount, 2);
                $invoice->status = bccomp((string) $invoice->paid_amount, '0.00', 2) === 0
                    ? InvoiceStatus::Issued
                    : InvoiceStatus::PartiallyPaid;
                $invoice->save();
            }

            $customer = Customer::whereKey($payment->customer_id)
                ->where('company_id', $payment->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $customer->balance = bcadd((string) $customer->balance, (string) $payment->allocated_amount, 2);
            $customer->save();
            if ($credit) {
                $credit->update(['remaining_amount' => 0, 'status' => 'reversed']);
            }
            $payment->update([
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'notes' => ($payment->notes ? $payment->notes."\n" : '')."Reversed: {$reason}",
            ]);
            Reversal::create([
                'company_id' => $payment->company_id,
                'original_type' => Payment::class,
                'original_id' => $payment->id,
                'action' => 'reverse',
                'performed_by' => $userId,
                'reason' => trim($reason),
                'status' => 'completed',
                'amount' => $payment->amount,
                'result_type' => Payment::class,
                'result_id' => $payment->id,
            ]);

            return $payment;
        });
    }

    private function ensureCashBox(int $userId, int $companyId): CashBox
    {
        $user = User::withoutGlobalScopes()->whereKey($userId)->lockForUpdate()->firstOrFail();
        if (! $user->hasCompanyAccess($companyId)) {
            throw new DomainException('Cash box user does not belong to this company.');
        }
        $cashBox = CashBox::withoutGlobalScopes()->where('user_id', $userId)->lockForUpdate()->first();
        if (! $cashBox) {
            $cashBox = CashBox::create(['company_id' => $companyId, 'user_id' => $userId, 'balance' => 0]);
        }
        if ($cashBox->company_id === null) {
            $cashBox->update(['company_id' => $companyId]);
        }

        return $cashBox->refresh();
    }

    private function assertIntentMatches(
        Payment $payment,
        int $userId,
        int $customerId,
        ?int $invoiceId,
        string $amount,
        string $method,
    ): void {
        if ((int) $payment->user_id !== $userId
            || (int) $payment->customer_id !== $customerId
            || ($payment->invoice_id === null ? null : (int) $payment->invoice_id) !== $invoiceId
            || (string) $payment->amount !== $amount
            || $payment->method !== $method) {
            throw new DomainException('Payment intent was already used with a different payload.');
        }
    }
}
