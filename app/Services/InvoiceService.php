<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockReason;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProformaInvoice;
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

            // Support both multi-line (items array) and single-line (legacy)
            $items = $data['items'] ?? [
                ['product_id' => $data['product_id'], 'quantity' => $data['quantity'], 'unit_price' => $data['unit_price'], 'batch_id' => $data['batch_id'] ?? null],
            ];

            $products = Product::whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');

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
                'user_id' => auth()->id(),
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

            // Van stock: look up rep's van warehouse
            $vanWarehouse = Warehouse::where('user_id', auth()->id())
                ->where('type', 'van')->first();

            if ($vanWarehouse) {
                foreach ($items as $item) {
                    $this->stock->decrement(
                        $vanWarehouse->id,
                        $item['product_id'],
                        $item['batch_id'] ?? null,
                        (float) $item['quantity'],
                        StockReason::Sale,
                        $invoice,
                        auth()->id(),
                    );
                }
            }

            // Customer balance update
            $customer = Customer::findOrFail($data['customer_id']);
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

            $vanWarehouse = Warehouse::where('user_id', $invoice->user_id)
                ->where('type', 'van')->first();

            if ($vanWarehouse) {
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
            }

            return $invoice->fresh();
        });
    }

    public function cancel(Invoice $invoice, int $userId, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $userId): Invoice {
            $invoice->update([
                'status' => InvoiceStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            // Reverse stock
            $vanWarehouse = Warehouse::where('user_id', $invoice->user_id)
                ->where('type', 'van')->first();

            if ($vanWarehouse) {
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
            }

            // Reverse customer balance — only the unpaid portion
            $unpaidAmount = (float) $invoice->total - (float) $invoice->paid_amount;
            if ($unpaidAmount > 0) {
                $invoice->customer->decrement('balance', $unpaidAmount);
            }

            return $invoice;
        });
    }

    public function amend(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $this->cancel($invoice, auth()->id(), 'Amendment requested');

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
}
