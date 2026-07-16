<?php

namespace App\Livewire\App;

use App\Models\CompanyBankAccount;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class QuotationFlow extends Component
{
    public ?PriceQuotationRequest $request = null;

    public ?PriceQuotation $quotation = null;

    public float $negotiatedPrice = 0;

    public float $floor = 0;

    public float $ceiling = 0;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public string $step = 'list'; // list, detail, proforma, done

    public function mount(): void
    {
        $this->step = 'list';
    }

    public function selectQuotation(int $id): void
    {
        $this->request = PriceQuotationRequest::with(['product', 'customer', 'quotation'])->findOrFail($id);
        $q = $this->request->quotation;
        $this->quotation = $q;
        $this->negotiatedPrice = (float) $this->request->quotation?->base_price ?? 0;
        $this->floor = $q ? (float) $q->base_price - (float) $q->rep_minus : 0;
        $this->ceiling = $q ? (float) $q->base_price + (float) $q->rep_plus : 0;
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->step = 'detail';
    }

    public function confirmPrice(): void
    {
        if (! $this->quotation) {
            return;
        }

        if ($this->negotiatedPrice < $this->floor) {
            $this->errorMessage = __('errors.price.out_of_range', [
                'price' => $this->negotiatedPrice,
                'product' => $this->request->product->name_ar ?? '',
            ]);

            return;
        }

        $this->request->update(['status' => 'confirmed']);
        $this->successMessage = __('app.price_confirmed');
        $this->step = 'detail';
    }

    public function createProforma(): void
    {
        if (! $this->quotation || ! $this->request) {
            return;
        }

        if ($this->negotiatedPrice < $this->floor) {
            $this->errorMessage = __('errors.price.out_of_range', [
                'price' => $this->negotiatedPrice,
                'product' => $this->request->product->name_ar ?? '',
            ]);

            return;
        }

        $company = $this->request->company;
        $bank = CompanyBankAccount::where('company_id', $company->id)->where('is_default', true)->first();
        $proformaNumber = 'PF-'.($company->abbr ?? 'GPC').'-'.date('Y').'-'.str_pad((string) (ProformaInvoice::max('id') + 1), 5, '0', STR_PAD_LEFT);

        $product = $this->request->product;
        $qty = (float) $this->request->quantity_requested;
        $unitPrice = $this->negotiatedPrice;
        $lineTotal = round($qty * $unitPrice, 2);
        $vatAmount = $product->vat_applicable ? round($lineTotal * ((float) $company->vat_percent / 100), 2) : 0;
        $total = round($lineTotal + $vatAmount, 2);

        $proforma = ProformaInvoice::create([
            'company_id' => $company->id,
            'customer_id' => $this->request->customer_id,
            'user_id' => auth()->id(),
            'visit_id' => $this->request->visit_id,
            'price_quotation_id' => $this->quotation->id,
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

        $this->step = 'proforma';
        $this->successMessage = __('app.proforma_created').' #'.$proformaNumber;

        session()->flash('proforma', $proforma);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.app.quotation-flow', [
            'requests' => PriceQuotationRequest::query()
                ->where('user_id', $user->id)
                ->with(['product', 'customer', 'quotation'])
                ->latest()
                ->take(20)
                ->get(),
            'priced' => PriceQuotationRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'priced')
                ->with(['product', 'customer', 'quotation'])
                ->latest()
                ->take(20)
                ->get(),
        ]);
    }
}
