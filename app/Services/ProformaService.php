<?php

namespace App\Services;

use App\Models\CompanyBankAccount;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\ProformaService as ProformaContract;
use Illuminate\Support\Facades\DB;

class ProformaService implements ProformaContract
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
    ) {}

    public function createFromQuotation(PriceQuotation $quotation, array $data): ProformaInvoice
    {
        if ($quotation->valid_until && $quotation->valid_until->isPast()) {
            throw new \DomainException(
                app()->getLocale() === 'ar'
                    ? 'انتهت صلاحية عرض السعر'
                    : 'This quotation has expired'
            );
        }

        return DB::transaction(function () use ($quotation, $data): ProformaInvoice {
            // ponytail: proformas intentionally skip stock checks — they are pre-sale
            // documents, not actual sales. Stock is checked at invoice creation time.
            $request = PriceQuotationRequest::with(['company', 'customer', 'product'])->findOrFail($quotation->price_quotation_request_id);
            $company = $request->company;
            $product = $request->product;
            $qty = (float) ($data['quantity'] ?? $request->quantity_requested);
            $unitPrice = (float) ($data['unit_price'] ?? $quotation->base_price);
            $lineTotal = round($qty * $unitPrice, 2);
            $vatAmount = $product->vat_applicable ? round($lineTotal * ((float) $company->vat_percent / 100), 2) : 0;
            $total = round($lineTotal + $vatAmount, 2);

            $proformaNumber = $this->numbers->generate('proforma', $company->id);

            $bank = CompanyBankAccount::where('company_id', $company->id)
                ->where('is_default', true)
                ->first();

            $proforma = ProformaInvoice::create([
                'company_id' => $company->id,
                'customer_id' => $request->customer_id,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'visit_id' => $request->visit_id,
                'price_quotation_id' => $quotation->id,
                'proforma_number' => $proformaNumber,
                'subtotal' => $lineTotal,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'company_bank_account_id' => $bank?->id,
                'status' => 'sent',
                'posting_date' => today(),
            ]);

            ProformaInvoiceItem::create([
                'proforma_invoice_id' => $proforma->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            return $proforma->fresh(['items']);
        });
    }
}
