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
            $prod = Product::findOrFail($data['product_id']);
            $qty = (float) $data['quantity'];
            $unitPrice = (float) $data['unit_price'];

            $calculation = $this->calc->calculate(
                [new LineItemInput($qty, $unitPrice, (bool) $prod->vat_applicable)],
                (float) $company->vat_percent,
            );

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

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $prod->id,
                'batch_id' => $data['batch_id'] ?? null,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                // ponytail: single-line only; iterate $calculation->lines when multi-line
                'line_total' => $calculation->lines[0]->lineTotal,
            ]);

            // Van stock: look up rep's van warehouse
            $vanWarehouse = Warehouse::where('user_id', auth()->id())
                ->where('type', 'van')->first();

            if ($vanWarehouse) {
                $this->stock->decrement(
                    $vanWarehouse->id,
                    $prod->id,
                    $data['batch_id'] ?? null,
                    $qty,
                    StockReason::Sale,
                    $invoice,
                    auth()->id(),
                );
            }

            // Customer balance update
            $customer = Customer::findOrFail($data['customer_id']);
            $customer->increment('balance', $calculation->total);

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

            // Reverse customer balance
            $invoice->customer->decrement('balance', (float) $invoice->total);

            return $invoice;
        });
    }

    public function amend(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $this->cancel($invoice, auth()->id(), 'Amendment requested');

            $draft = Invoice::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'user_id' => auth()->id(),
                'invoice_number' => $invoice->invoice_number,
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
