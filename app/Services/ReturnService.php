<?php

namespace App\Services;

use App\Data\ReturnStockDestination;
use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ReturnItem;
use App\Models\ReturnRecord;
use App\Models\Reversal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\StockService;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(private readonly StockService $stock) {}

    /** @param array<int, array{invoice_item_id: int, quantity: float, condition?: string, unit_price?: float, line_total?: float, tax_amount?: float, total?: float}> $items */
    public function create(
        int $companyId,
        int $userId,
        int $customerId,
        array $items,
        ?int $againstInvoiceId = null,
        ?int $visitId = null,
        string $reason = '',
        ?ReturnStockDestination $stockDestination = null,
    ): ReturnRecord {
        app(ActiveCompanyContext::class)->assertMatches($companyId);

        if ($againstInvoiceId === null) {
            throw new DomainException('Returns must reference an original issued invoice.');
        }
        if ($items === []) {
            throw new DomainException('A return requires at least one invoice line.');
        }

        return DB::transaction(function () use (
            $companyId,
            $userId,
            $customerId,
            $items,
            $againstInvoiceId,
            $visitId,
            $reason,
            $stockDestination,
        ): ReturnRecord {
            $representative = User::withoutGlobalScopes()->whereKey($userId)->lockForUpdate()->firstOrFail();
            if (! $representative->hasCompanyAccess($companyId) || ! $representative->can('create:return_record')) {
                throw new DomainException('Only an assigned sales rep may create an invoice-linked return.');
            }
            $invoice = Invoice::whereKey($againstInvoiceId)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();
            if (! $invoice || (int) $invoice->customer_id !== $customerId) {
                throw new DomainException('The original invoice does not belong to this company and customer.');
            }
            if (! in_array($invoice->status, [
                InvoiceStatus::Issued,
                InvoiceStatus::Submitted,
                InvoiceStatus::PartiallyPaid,
                InvoiceStatus::Paid,
            ], true)) {
                throw new DomainException('Returns are allowed only against a non-terminal issued invoice.');
            }

            $customer = Customer::whereKey($customerId)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();
            if ($stockDestination === null) {
                $stockActorId = $userId;
                $sellableWarehouse = Warehouse::where('user_id', $userId)
                    ->where('company_id', $companyId)->where('type', 'van')->where('is_active', true)
                    ->lockForUpdate()->first();
                $quarantine = Warehouse::where('company_id', $companyId)->where('type', 'quarantine')
                    ->where('is_active', true)->lockForUpdate()->first();
                if ($sellableWarehouse === null) {
                    throw new DomainException('An active same-company van warehouse is required for field returns.');
                }
            } else {
                $stockActor = User::withoutGlobalScopes()->whereKey($stockDestination->stockActorId)
                    ->lockForUpdate()->firstOrFail();
                if (! $stockActor->hasCompanyAccess($companyId) || ! $stockActor->can('return_requests.receive')) {
                    throw new DomainException('Only an authorized same-company warehouse user may receive this return.');
                }
                $stockActorId = (int) $stockActor->id;
                $sellableWarehouse = Warehouse::withoutGlobalScopes()
                    ->whereKey($stockDestination->sellableWarehouseId)->where('company_id', $companyId)
                    ->where('type', 'main')->where('is_active', true)->lockForUpdate()->first();
                if ($sellableWarehouse === null) {
                    throw new DomainException('Sellable returns require an active same-company main warehouse.');
                }
                $quarantine = $stockDestination->quarantineWarehouseId === null ? null : Warehouse::withoutGlobalScopes()
                    ->whereKey($stockDestination->quarantineWarehouseId)->where('company_id', $companyId)
                    ->where('type', 'quarantine')->where('is_active', true)->lockForUpdate()->first();
            }

            $return = ReturnRecord::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'user_id' => $userId,
                'visit_id' => $visitId,
                'against_invoice_id' => $invoice->id,
                'destination_warehouse_id' => $sellableWarehouse->id,
                'quarantine_warehouse_id' => $quarantine?->id,
                'return_number' => app(DocumentNumberService::class)->generate('sales_return', $companyId),
                'total' => 0,
                'reason' => $reason,
                'status' => 'submitted',
                'returned_at' => now(),
                'posting_date' => today(),
            ]);

            $subtotal = '0.00';
            $taxTotal = '0.00';
            $seen = [];
            foreach ($items as $input) {
                $invoiceItemId = (int) $input['invoice_item_id'];
                if ($invoiceItemId < 1 || isset($seen[$invoiceItemId])) {
                    throw new DomainException('Each returned invoice line must be present exactly once.');
                }
                $seen[$invoiceItemId] = true;

                $original = InvoiceItem::whereKey($invoiceItemId)
                    ->where('invoice_id', $invoice->id)
                    ->lockForUpdate()
                    ->first();
                if (! $original) {
                    throw new DomainException('The returned line is not part of the original invoice.');
                }

                $quantity = number_format((float) $input['quantity'], 3, '.', '');
                if (bccomp($quantity, '0.000', 3) <= 0) {
                    throw new DomainException('Return quantity must be greater than zero.');
                }
                $prior = (string) ReturnItem::query()
                    ->where('invoice_item_id', $original->id)
                    ->whereHas('return', fn ($query) => $query->where('status', '!=', 'cancelled'))
                    ->sum('quantity');
                $remaining = bcsub((string) $original->quantity, $prior, 3);
                if (bccomp($quantity, $remaining, 3) > 0) {
                    throw new DomainException('Return quantity exceeds sold quantity less prior accepted returns.');
                }

                $condition = (string) ($input['condition'] ?? 'sellable');
                if (! in_array($condition, ['sellable', 'damaged'], true)) {
                    throw new DomainException('Return condition must be sellable or damaged.');
                }
                if ($condition === 'damaged' && $quarantine === null) {
                    throw new DomainException('Damaged returns require an active quarantine warehouse.');
                }

                $lineTotal = number_format((float) bcmul($quantity, (string) $original->unit_price, 5), 2, '.', '');
                $taxAmount = number_format(
                    (float) bcmul(
                        (string) $original->tax_amount,
                        bcdiv($quantity, (string) $original->quantity, 8),
                        8,
                    ),
                    2,
                    '.',
                    '',
                );
                $lineGross = bcadd($lineTotal, $taxAmount, 2);
                if ($stockDestination !== null) {
                    $this->assertSnapshot($input, 'unit_price', (string) $original->unit_price, 2);
                    $this->assertSnapshot($input, 'line_total', $lineTotal, 2);
                    $this->assertSnapshot($input, 'tax_amount', $taxAmount, 2);
                    $this->assertSnapshot($input, 'total', $lineGross, 2);
                }

                $returnItem = ReturnItem::create([
                    'return_id' => $return->id,
                    'invoice_item_id' => $original->id,
                    'product_id' => $original->product_id,
                    'batch_id' => $original->batch_id,
                    'condition' => $condition,
                    'quantity' => $quantity,
                    'unit_price' => $original->unit_price,
                    'line_total' => $lineTotal,
                    'tax_amount' => $taxAmount,
                    'total' => $lineGross,
                ]);
                $subtotal = bcadd($subtotal, $lineTotal, 2);
                $taxTotal = bcadd($taxTotal, $taxAmount, 2);

                $destination = $condition === 'damaged' ? $quarantine : $sellableWarehouse;
                $this->stock->increment(
                    $destination->id,
                    $original->product_id,
                    $original->batch_id,
                    (float) $quantity,
                    StockReason::Return,
                    $return,
                    $stockActorId,
                );
            }

            $total = bcadd($subtotal, $taxTotal, 2);
            $return->update(['total' => $total]);

            $creditNote = CreditNote::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'invoice_id' => $invoice->id,
                'return_id' => $return->id,
                'created_by' => $stockActorId,
                'credit_number' => app(DocumentNumberService::class)->generate('credit_note', $companyId),
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,
                'status' => 'issued',
                'reason' => $reason !== '' ? $reason : 'Invoice-linked return',
                'issued_at' => now(),
            ]);
            foreach ($return->load('items')->items as $item) {
                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'invoice_item_id' => $item->invoice_item_id,
                    'return_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'batch_id' => $item->batch_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                ]);
            }

            $receivableCredit = bccomp($total, (string) $invoice->remaining_amount, 2) > 0
                ? (string) $invoice->remaining_amount
                : $total;
            if (bccomp($receivableCredit, '0.00', 2) > 0) {
                $invoice->remaining_amount = bcsub((string) $invoice->remaining_amount, $receivableCredit, 2);
                $customer->balance = bcsub((string) $customer->balance, $receivableCredit, 2);
                $customer->save();
            }
            $invoice->credited_amount = bcadd((string) ($invoice->credited_amount ?? '0.00'), $total, 2);
            if (bccomp((string) $invoice->credited_amount, (string) $invoice->total, 2) >= 0) {
                $invoice->status = InvoiceStatus::Credited;
            }
            $invoice->save();

            $unallocatedCredit = bcsub($total, $receivableCredit, 2);
            if (bccomp($unallocatedCredit, '0.00', 2) > 0) {
                CustomerCredit::create([
                    'company_id' => $companyId,
                    'customer_id' => $customerId,
                    'invoice_id' => $invoice->id,
                    'return_id' => $return->id,
                    'created_by' => $stockActorId,
                    'credit_number' => app(DocumentNumberService::class)->generate('credit_note', $companyId),
                    'amount' => $unallocatedCredit,
                    'remaining_amount' => $unallocatedCredit,
                    'status' => 'available',
                    'reason' => 'Credit from paid invoice return',
                ]);
            }

            return $return->fresh(['items']);
        });
    }

    public function cancel(ReturnRecord $return, int $userId, string $reason): ReturnRecord
    {
        $manager = User::withoutGlobalScopes()->findOrFail($userId);
        if (! $manager->can('update:return_record')
            || ! $manager->hasCompanyAccess((int) $return->company_id)
            || trim($reason) === '') {
            throw new DomainException('A sales manager and mandatory reason are required for a return reversal.');
        }

        return DB::transaction(function () use ($return, $userId, $reason): ReturnRecord {
            $return = ReturnRecord::whereKey($return->id)->lockForUpdate()->firstOrFail();
            $existing = Reversal::where('original_type', ReturnRecord::class)
                ->where('original_id', $return->id)
                ->where('action', 'reverse')
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $return;
            }
            if ($return->status !== 'submitted') {
                throw new DomainException('Only a committed return may be reversed.');
            }
            if ($return->returned_at && $return->returned_at->diffInDays(now()) > 7) {
                throw new DomainException('Return reversals are only allowed within 7 days of return.');
            }

            $invoice = Invoice::whereKey($return->against_invoice_id)
                ->where('company_id', $return->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $hasDependentPayment = $invoice->payments()
                ->whereNull('cancelled_at')
                ->where('created_at', '>', $return->created_at)
                ->exists();
            if ($hasDependentPayment) {
                throw new DomainException('Later invoice payments must be reversed before this return.');
            }

            $credit = CustomerCredit::where('return_id', $return->id)->lockForUpdate()->first();
            if ($credit && bccomp((string) $credit->remaining_amount, (string) $credit->amount, 2) !== 0) {
                throw new DomainException('Customer credit has dependent usage and cannot be reversed.');
            }
            $creditNote = CreditNote::where('return_id', $return->id)->lockForUpdate()->firstOrFail();
            if ($creditNote->status !== 'issued') {
                throw new DomainException('The linked credit note is not eligible for reversal.');
            }

            // Check if the credit note has been applied to any invoice
            $creditApplied = Invoice::where('company_id', $return->company_id)
                ->where('credited_amount', '>', 0)
                ->where('id', '!=', $return->against_invoice_id)
                ->where('updated_at', '>', $creditNote->created_at)
                ->exists();
            if ($creditApplied) {
                throw new DomainException('The credit note has been applied to other invoices and cannot be reversed.');
            }

            $customer = Customer::whereKey($return->customer_id)
                ->where('company_id', $return->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            $sellableWarehouse = Warehouse::where('company_id', $return->company_id)
                ->when(
                    $return->destination_warehouse_id !== null,
                    fn ($query) => $query->whereKey($return->destination_warehouse_id),
                    fn ($query) => $query->where('user_id', $return->user_id)->where('type', 'van'),
                )
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $quarantine = Warehouse::where('company_id', $return->company_id)
                ->where('type', 'quarantine')
                ->when($return->quarantine_warehouse_id !== null, fn ($query) => $query->whereKey($return->quarantine_warehouse_id))
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            foreach ($return->items()->lockForUpdate()->get() as $item) {
                $source = $item->condition === 'damaged' ? $quarantine : $sellableWarehouse;
                if ($source === null) {
                    throw new DomainException('The original return stock location is unavailable.');
                }
                $this->stock->decrement(
                    $source->id,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    StockReason::Reversal,
                    $return,
                    $userId,
                );
            }

            $unallocatedCredit = $credit ? (string) $credit->amount : '0.00';
            $receivableCredit = bcsub((string) $return->total, $unallocatedCredit, 2);
            if (bccomp($receivableCredit, '0.00', 2) > 0) {
                $customer->balance = bcadd((string) $customer->balance, $receivableCredit, 2);
                $customer->save();
                $invoice->remaining_amount = bcadd(
                    (string) $invoice->remaining_amount,
                    $receivableCredit,
                    2,
                );
            }
            $invoice->credited_amount = bcsub(
                (string) $invoice->credited_amount,
                (string) $return->total,
                2,
            );
            $invoice->status = bccomp((string) $invoice->remaining_amount, '0.00', 2) === 0
                ? InvoiceStatus::Paid
                : (bccomp((string) $invoice->paid_amount, '0.00', 2) > 0
                    ? InvoiceStatus::PartiallyPaid
                    : InvoiceStatus::Issued);
            $invoice->save();

            if ($credit) {
                $credit->update(['remaining_amount' => '0.00', 'status' => 'reversed']);
            }
            $creditNote->update(['status' => 'reversed']);
            $return->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);
            Reversal::create([
                'company_id' => $return->company_id,
                'original_type' => ReturnRecord::class,
                'original_id' => $return->id,
                'action' => 'reverse',
                'performed_by' => $userId,
                'reason' => trim($reason),
                'status' => 'completed',
                'amount' => $return->total,
                'result_type' => CreditNote::class,
                'result_id' => $creditNote->id,
            ]);

            return $return->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $input */
    private function assertSnapshot(array $input, string $field, string $actual, int $scale): void
    {
        if (array_key_exists($field, $input) && bccomp((string) $input[$field], $actual, $scale) !== 0) {
            throw new DomainException('The approved return value no longer matches the immutable invoice snapshot.');
        }
    }
}
