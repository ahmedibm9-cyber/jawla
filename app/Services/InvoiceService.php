<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceCalculationService;
use App\Services\Contracts\InvoiceService as InvoiceContract;
use App\Services\Contracts\LineItemInput;
use App\Services\Contracts\StockService as StockContract;
use Illuminate\Support\Facades\DB;

class InvoiceService implements InvoiceContract
{
    public function __construct(
        private readonly StockContract $stock,
        private readonly InvoiceCalculationService $calc,
        private readonly DocumentNumberService $numbers,
    ) {}

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $company = Company::findOrFail($data['company_id']);
            $sellerId = $data['user_id'] ?? auth()->id();
            $seller = User::withoutGlobalScopes()
                ->whereKey($sellerId)
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();
            if (! $seller) {
                throw new \DomainException($this->companyMessage('seller'));
            }

            $vanWarehouse = $this->vanWarehouseFor($seller->id, $company->id);

            // Support both multi-line (items array) and single-line (legacy)
            $items = $data['items'] ?? [
                ['product_id' => $data['product_id'], 'quantity' => $data['quantity'], 'unit_price' => $data['unit_price'], 'batch_id' => $data['batch_id'] ?? null],
            ];

            $productIds = array_values(array_unique(array_column($items, 'product_id')));
            $products = Product::where('company_id', $company->id)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');
            if ($products->count() !== count($productIds)) {
                throw new \DomainException($this->companyMessage('product'));
            }

            $customer = Customer::whereKey($data['customer_id'])
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();
            if (! $customer) {
                throw new \DomainException($this->companyMessage('customer'));
            }

            // Issue 13: Customer must be approved before creating invoices
            if (($customer->status ?? 'approved') !== 'approved') {
                $message = match($customer->status) {
                    'pending' => app()->getLocale() === 'ar'
                        ? 'العميل في انتظار موافقة الإدارة ولا يمكن إنشاء فاتورة له'
                        : 'Customer is pending admin approval and cannot be invoiced yet',
                    'rejected' => app()->getLocale() === 'ar'
                        ? 'تم رفض هذا العميل من الإدارة ولا يمكن إنشاء فاتورة له'
                        : 'This customer was rejected by admin and cannot be invoiced',
                    default => app()->getLocale() === 'ar'
                        ? 'حالة العميل غير صالحة'
                        : 'Invalid customer status',
                };
                throw new \DomainException($message);
            }

            $lineInputs = [];
            foreach ($items as $item) {
                $prod = $products->get($item['product_id']);
                $lineInputs[] = new LineItemInput(
                    qty: (float) $item['quantity'],
                    unitPrice: (float) $item['unit_price'],
                    vatApplicable: (bool) ($prod?->vat_applicable ?? true),
                );
            }

            $calculation = $this->calc->calculate($lineInputs, (float) $company->vat_percent);

            $invNumber = $this->numbers->generate('sales_invoice', $company->id);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $data['customer_id'],
                'user_id' => $sellerId,
                'visit_id' => $data['visit_id'] ?? null,
                'proforma_invoice_id' => $data['proforma_invoice_id'] ?? null,
                'invoice_number' => $invNumber,
                'status' => InvoiceStatus::Submitted,
                'subtotal' => $calculation->subtotal,
                'vat_amount' => $calculation->vatAmount,
                'total' => $calculation->total,
                'paid_amount' => 0,
                'remaining_amount' => $calculation->total,
                'posting_date' => today(),
                'issued_at' => now(),
            ]);

            foreach ($items as $i => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $calculation->lines[$i]->lineTotal,
                ]);
            }

            foreach ($items as $item) {
                $this->stock->decrement(
                    $vanWarehouse->id,
                    $item['product_id'],
                    $item['batch_id'] ?? null,
                    (float) $item['quantity'],
                    StockReason::Sale,
                    $invoice,
                    $sellerId,
                );
            }

            // Customer balance update
            $customer->increment('balance', (float) $calculation->total);

            // If from proforma, mark it converted
            if (isset($data['proforma_invoice_id'])) {
                ProformaInvoice::where('id', $data['proforma_invoice_id'])
                    ->update(['status' => 'converted_to_invoice']);
            }

            return $invoice;
        });
    }

    public function submit(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            if ($invoice->status !== InvoiceStatus::Draft) {
                throw new \RuntimeException('Only draft invoices can be submitted.');
            }

            $invoice->update([
                'status' => InvoiceStatus::Submitted,
                'issued_at' => now(),
            ]);

            $vanWarehouse = $this->vanWarehouseFor($invoice->user_id, $invoice->company_id);

            foreach ($invoice->items as $item) {
                $this->stock->decrement(
                    $vanWarehouse->id,
                    $item->product_id,
                    $item->batch_id,
                    (float) $item->quantity,
                    StockReason::Sale,
                    $invoice,
                    $invoice->user_id,
                );
            }

            return $invoice->fresh();
        });
    }

    public function cancel(Invoice $invoice, int $userId, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $userId, $reason): Invoice {
            $this->cancelWithoutTransaction($invoice, $userId, $reason);

            return $invoice;
        });
    }

    public function amend(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            // Cancel the original invoice (no nested transaction)
            $this->cancelWithoutTransaction($invoice, auth()->id(), 'Amendment requested');

            $company = $invoice->company;
            $newNumber = $this->numbers->generate('sales_invoice', $company->id);

            $draft = Invoice::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'user_id' => auth()->id(),
                'invoice_number' => $newNumber,
                'status' => InvoiceStatus::Draft,
                'subtotal' => $invoice->subtotal,
                'vat_amount' => $invoice->vat_amount,
                'total' => $invoice->total,
                'paid_amount' => 0,
                'remaining_amount' => $invoice->total,
                'amended_from' => $invoice->id,
                'posting_date' => today(),
                'issued_at' => null,
            ]);

            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $draft->id,
                    'product_id' => $item->product_id,
                    'batch_id' => $item->batch_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ]);
            }

            return $draft->fresh(['items']);
        });
    }

    private function cancelWithoutTransaction(Invoice $invoice, int $userId, string $reason): void
    {
        // Re-fetch with lock to prevent double-cancel race condition
        $invoice = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

        // Guard: don't cancel if already cancelled
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return;
        }

        $invoice->update([
            'status' => InvoiceStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
        ]);

        // Reverse stock
        $vanWarehouse = $this->vanWarehouseFor($invoice->user_id, $invoice->company_id);
        foreach ($invoice->items as $item) {
            $this->stock->increment(
                $vanWarehouse->id,
                $item->product_id,
                $item->batch_id,
                (float) $item->quantity,
                StockReason::Adjustment,
                $invoice,
                $userId,
            );
        }

        // Reverse customer balance — only the unpaid portion
        $unpaidAmount = (float) $invoice->total - (float) $invoice->paid_amount;
        if ($unpaidAmount > 0) {
            Customer::whereKey($invoice->customer_id)
                ->where('company_id', $invoice->company_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->decrement('balance', $unpaidAmount);
        }

        // Log the reversal activity
        Activity::log('invoice_reversed', $invoice, 'Invoice '.$invoice->invoice_number.' reversed: '.$reason);
    }

    private function vanWarehouseFor(?int $userId, int $companyId): Warehouse
    {
        if ($userId === null) {
            throw new \DomainException('A seller is required to create or submit an invoice.');
        }

        $warehouse = Warehouse::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('type', 'van')
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $warehouse) {
            throw new \DomainException(
                app()->getLocale() === 'ar'
                    ? 'لا يمكن إتمام البيع بدون مخزن سيارة نشط للمندوب'
                    : 'A sale requires an active van warehouse for the seller.'
            );
        }

        return $warehouse;
    }

    private function companyMessage(string $resource): string
    {
        $english = ucfirst($resource).' does not belong to this company.';
        $arabic = match ($resource) {
            'seller' => 'المندوب لا يتبع هذه الشركة.',
            'customer' => 'العميل لا يتبع هذه الشركة.',
            'product' => 'المنتج لا يتبع هذه الشركة.',
            default => 'السجل لا يتبع هذه الشركة.',
        };

        return app()->getLocale() === 'ar' ? $arabic : $english;
    }
}
