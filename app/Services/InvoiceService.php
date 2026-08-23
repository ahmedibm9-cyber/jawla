<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Exceptions\Domain\DomainException;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\Reversal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceCalculationService;
use App\Services\Contracts\InvoiceService as InvoiceContract;
use App\Services\Contracts\LineItemInput;
use App\Services\Contracts\PricingService as PricingContract;
use App\Services\Contracts\StockService as StockContract;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService implements InvoiceContract
{
    public function __construct(
        private readonly StockContract $stock,
        private readonly InvoiceCalculationService $calc,
        private readonly DocumentNumberService $numbers,
        private readonly PricingContract $pricing,
    ) {}

    public function create(array $data): Invoice
    {
        app(ActiveCompanyContext::class)->assertMatches((int) $data['company_id']);

        return DB::transaction(function () use ($data): Invoice {
            $company = Company::findOrFail($data['company_id']);
            $sellerId = $data['user_id'] ?? auth()->id();
            $seller = User::withoutGlobalScopes()
                ->whereKey($sellerId)
                ->lockForUpdate()
                ->first();
            if (! $seller || ! $seller->hasCompanyAccess($company->id) || ! $seller->can('create:invoice')) {
                throw new DomainException('errors.resource.seller');
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
                throw new DomainException('errors.resource.product');
            }

            $customer = Customer::whereKey($data['customer_id'])
                ->where('company_id', $company->id)
                ->lockForUpdate()
                ->first();
            if (! $customer) {
                throw new DomainException('errors.resource.customer');
            }

            // Issue 13: Customer must be approved before creating invoices
            if (($customer->status ?? 'approved') !== 'approved') {
                $message = match ($customer->status) {
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
                throw new DomainException($message);
            }

            $lineInputs = [];
            foreach ($items as $index => $item) {
                if (bccomp(number_format((float) $item['quantity'], 3, '.', ''), '0.000', 3) <= 0) {
                    throw new DomainException('Invoice line quantities must be positive.');
                }

                $prod = $products->get($item['product_id']);
                $effectivePrice = $this->pricing->effectivePrice(
                    $company->id,
                    $customer->id,
                    (int) $item['product_id'],
                    number_format((float) $item['quantity'], 3, '.', ''),
                );
                if (array_key_exists('unit_price', $item)
                    && bccomp(number_format((float) $item['unit_price'], 2, '.', ''), $effectivePrice, 2) !== 0) {
                    throw new DomainException('Quoted price is stale or does not match the server-authoritative price.');
                }
                $items[$index]['unit_price'] = $effectivePrice;
                $lineInputs[] = new LineItemInput(
                    qty: (string) $item['quantity'],
                    unitPrice: (string) $effectivePrice,
                    vatApplicable: (bool) ($prod?->vat_applicable ?? true),
                );
            }

            $calculation = $this->calc->calculate($lineInputs, (string) $company->vat_percent);

            $invNumber = $this->numbers->generate('sales_invoice', $company->id);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $data['customer_id'],
                'user_id' => $sellerId,
                'visit_id' => $data['visit_id'] ?? null,
                'proforma_invoice_id' => $data['proforma_invoice_id'] ?? null,
                'invoice_number' => $invNumber,
                'status' => InvoiceStatus::Issued,
                'subtotal' => $calculation->subtotal,
                'vat_amount' => $calculation->vatAmount,
                'total' => $calculation->total,
                'paid_amount' => 0,
                'remaining_amount' => $calculation->total,
                'posting_date' => today(),
                'issued_at' => now(),
                'snapshot_company' => $this->buildCompanySnapshot($company),
                'snapshot_customer' => $this->buildCustomerSnapshot($customer),
                'snapshot_totals' => [
                    'subtotal' => $calculation->subtotal,
                    'vat_amount' => $calculation->vatAmount,
                    'total' => $calculation->total,
                    'currency' => 'EGP',
                    'vat_percent' => $company->vat_percent,
                ],
            ]);

            $snapshotItems = [];
            foreach ($items as $i => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $item['batch_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $calculation->lines[$i]->lineTotal,
                    'tax_amount' => $calculation->lines[$i]->vatAmount,
                ]);
                $prod = $products->get($item['product_id']);
                $snapshotItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name_ar' => $prod->name_ar ?? '',
                    'product_name_en' => $prod->name_en ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $calculation->lines[$i]->lineTotal,
                    'tax_amount' => $calculation->lines[$i]->vatAmount,
                ];
            }
            $invoice->update(['snapshot_items' => $snapshotItems]);

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

            Log::info('Invoice created.', [
                'company_id' => $company->id,
                'user_id' => $sellerId,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invNumber,
                'customer_id' => $data['customer_id'],
                'total' => $calculation->total,
                'items_count' => count($items),
            ]);

            return $invoice;
        });
    }

    public function submit(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice = Invoice::with('items')->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== InvoiceStatus::Draft) {
                throw new \RuntimeException('Only draft invoices can be submitted.');
            }
            $seller = User::withoutGlobalScopes()->whereKey($invoice->user_id)->lockForUpdate()->firstOrFail();
            if (! $seller->hasCompanyAccess((int) $invoice->company_id) || ! $seller->can('update:invoice')) {
                throw new DomainException('Only an assigned sales rep may issue a draft invoice.');
            }

            $invoice->update([
                'invoice_number' => $this->numbers->generate('sales_invoice', $invoice->company_id),
                'status' => InvoiceStatus::Issued,
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

            Customer::whereKey($invoice->customer_id)
                ->where('company_id', $invoice->company_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('balance', (float) $invoice->total);

            return $invoice->fresh();
        });
    }

    public function cancel(Invoice $invoice, int $userId, string $reason): Invoice
    {
        $manager = User::withoutGlobalScopes()->findOrFail($userId);
        if (! $manager->can('invoices.cancel')
            || ! $manager->hasCompanyAccess((int) $invoice->company_id)
            || trim($reason) === '') {
            throw new DomainException('A sales manager and mandatory reason are required to void an issued invoice.');
        }

        return DB::transaction(function () use ($invoice, $userId, $reason): Invoice {
            $existing = Reversal::where('original_type', Invoice::class)
                ->where('original_id', $invoice->id)
                ->where('action', 'void')
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $invoice->fresh();
            }
            $locked = Invoice::with('items')->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! $locked->issued_at?->isToday()) {
                throw new DomainException('Only an eligible same-day invoice may be voided.');
            }
            if ($locked->payments()->whereNull('cancelled_at')->exists()) {
                throw new DomainException('An invoice with payments must be corrected by credit note, not voided.');
            }
            if ($locked->returns()->where('status', 'submitted')->exists()
                || $locked->creditNotes()->where('status', 'issued')->exists()) {
                throw new DomainException('An invoice with return or credit activity cannot be voided.');
            }
            $this->cancelWithoutTransaction($invoice, $userId, $reason);
            Reversal::create([
                'company_id' => $locked->company_id,
                'original_type' => Invoice::class,
                'original_id' => $locked->id,
                'action' => 'void',
                'performed_by' => $userId,
                'reason' => trim($reason),
                'status' => 'completed',
                'amount' => $locked->total,
                'result_type' => Invoice::class,
                'result_id' => $locked->id,
            ]);

            Log::warning('Invoice voided.', [
                'company_id' => $locked->company_id,
                'user_id' => $userId,
                'invoice_id' => $locked->id,
                'invoice_number' => $locked->invoice_number,
                'total' => $locked->total,
                'reason' => trim($reason),
            ]);

            return $invoice->fresh();
        });
    }

    public function amend(Invoice $invoice, string $reason = 'Amendment requested'): Invoice
    {
        $manager = auth()->user();
        if (! $manager?->can('update:invoice') || trim($reason) === '') {
            throw new DomainException('Only a sales manager may amend an issued invoice, with a mandatory reason.');
        }

        return DB::transaction(function () use ($invoice, $reason, $manager): Invoice {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! $manager->hasCompanyAccess((int) $invoice->company_id)) {
                throw new DomainException('The invoice belongs to another company.');
            }
            if (! in_array($invoice->status, [
                InvoiceStatus::Issued,
                InvoiceStatus::Submitted,
                InvoiceStatus::PartiallyPaid,
                InvoiceStatus::Paid,
            ], true)) {
                throw new DomainException('Only a non-terminal issued invoice may be amended.');
            }

            $returnItems = $invoice->items->map(fn (InvoiceItem $item) => [
                'invoice_item_id' => $item->id,
                'quantity' => (float) $item->quantity,
                'condition' => 'sellable',
            ])->all();
            app(ReturnService::class)->create(
                companyId: $invoice->company_id,
                userId: $invoice->user_id,
                customerId: $invoice->customer_id,
                items: $returnItems,
                againstInvoiceId: $invoice->id,
                reason: 'Invoice amendment: '.trim($reason),
            );

            $draft = Invoice::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'user_id' => $invoice->user_id,
                'invoice_number' => null,
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
                    'tax_amount' => $item->tax_amount,
                ]);
            }

            return $draft->fresh(['items']);
        });
    }

    private function cancelWithoutTransaction(Invoice $invoice, int $userId, string $reason): void
    {
        // Re-fetch with lock to prevent double-cancel race condition
        $invoice = Invoice::with('items')->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

        // Guard: don't cancel if already cancelled
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return;
        }

        $invoice->update([
            'status' => InvoiceStatus::Voided,
            'cancelled_at' => now(),
            'cancelled_by' => $userId,
        ]);

        $vanWarehouse = $this->vanWarehouseFor($invoice->user_id, $invoice->company_id);
        foreach ($invoice->items as $item) {
            $this->stock->increment(
                $vanWarehouse->id,
                $item->product_id,
                $item->batch_id,
                (float) $item->quantity,
                StockReason::Reversal,
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

    private function buildCompanySnapshot(Company $company): array
    {
        return [
            'name_ar' => $company->name_ar,
            'name_en' => $company->name_en,
            'tax_number' => $company->tax_number,
            'address' => $company->address,
            'vat_percent' => $company->vat_percent,
            'bank_name' => $company->bank_name,
            'bank_iban' => $company->bank_iban,
        ];
    }

    private function buildCustomerSnapshot(Customer $customer): array
    {
        return [
            'name_ar' => $customer->name_ar,
            'name_en' => $customer->name_en,
            'code' => $customer->code,
            'address' => $customer->address,
        ];
    }
}
