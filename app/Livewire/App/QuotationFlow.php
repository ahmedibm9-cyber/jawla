<?php

namespace App\Livewire\App;

use App\Models\CompanyBankAccount;
use App\Models\PriceQuotation;
use App\Models\PriceQuotationRequest;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Services\Contracts\DocumentNumberService;
use App\Services\Contracts\InvoiceCalculationService;
use App\Services\Contracts\LineItemInput;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class QuotationFlow extends Component
{
    use WithPagination;

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
        $this->request = PriceQuotationRequest::with(['product', 'customer', 'quotation', 'company'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);
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

    public function createProforma(DocumentNumberService $numbers, InvoiceCalculationService $calc): void
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
        $product = $this->request->product;
        $qty = (float) $this->request->quantity_requested;
        $unitPrice = $this->negotiatedPrice;

        $proformaNumber = $numbers->generate('proforma_invoice', $company->id);
        $bank = CompanyBankAccount::where('company_id', $company->id)->where('is_default', true)->first();

        $calculation = $calc->calculate(
            [new LineItemInput(qty: $qty, unitPrice: $unitPrice, vatApplicable: $product->vat_applicable)],
            (float) $company->vat_percent,
        );

        $proforma = DB::transaction(function () use ($company, $product, $qty, $unitPrice, $proformaNumber, $bank, $calculation): ProformaInvoice {
            $p = ProformaInvoice::create([
                'company_id' => $company->id,
                'customer_id' => $this->request->customer_id,
                'user_id' => auth()->id(),
                'visit_id' => $this->request->visit_id,
                'price_quotation_id' => $this->quotation->id,
                'proforma_number' => $proformaNumber,
                'subtotal' => $calculation->subtotal,
                'vat_amount' => $calculation->vatAmount,
                'total' => $calculation->total,
                'company_bank_account_id' => $bank?->id,
                'status' => 'sent',
                'posting_date' => today(),
            ]);

            ProformaInvoiceItem::create([
                'proforma_invoice_id' => $p->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $calculation->lines[0]->lineTotal,
            ]);

            return $p;
        });

        $this->step = 'proforma';
        $this->successMessage = __('app.proforma_created').' #'.$proforma->proforma_number;

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
                ->paginate(20),
            'priced' => PriceQuotationRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'priced')
                ->with(['product', 'customer', 'quotation'])
                ->latest()
                ->paginate(20),
        ]);
    }
}
