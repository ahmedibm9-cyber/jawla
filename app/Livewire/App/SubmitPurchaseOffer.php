<?php

namespace App\Livewire\App;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Services\PurchaseRequestService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class SubmitPurchaseOffer extends Component
{
    /** ID of a rejected offer being edited & resubmitted (D-04 renegotiation loop). */
    #[Url]
    public ?int $resubmit = null;

    public ?int $product_id = null;

    public ?int $supplier_id = null;

    public ?float $quantity = null;

    public ?float $offered_price = null;

    public string $currency = 'EGP';

    public string $payment_terms = '';

    public ?string $expires_at = null;

    public ?string $rejectionNotes = null;

    public ?string $successMessage = null;

    public function mount(): void
    {
        if (! $this->resubmit) {
            return;
        }

        $offer = PurchaseRequest::where('user_id', auth()->id())
            ->whereIn('status', ['rejected_by_sales', 'rejected_by_purchasing'])
            ->find($this->resubmit);

        if (! $offer) {
            $this->resubmit = null;

            return;
        }

        $this->product_id = $offer->product_id;
        $this->supplier_id = $offer->supplier_id;
        $this->quantity = (float) $offer->quantity;
        $this->offered_price = (float) $offer->offered_price;
        $this->currency = $offer->currency;
        $this->payment_terms = (string) $offer->payment_terms;
        $this->expires_at = $offer->expires_at?->toDateString();
        $this->rejectionNotes = $offer->status === 'rejected_by_sales'
            ? $offer->sales_review_notes
            : $offer->purchasing_review_notes;
    }

    public function submit(PurchaseRequestService $service): void
    {
        $validated = $this->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.001',
            'offered_price' => 'required|numeric|min:0.01',
            'currency' => 'required|in:EGP,USD,EUR,SAR',
            'payment_terms' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ]);

        $user = auth()->user();

        if ($this->resubmit) {
            $offer = PurchaseRequest::where('user_id', $user->id)->find($this->resubmit);

            if ($offer) {
                try {
                    $service->resubmit($offer, $user->id, $validated);
                } catch (\RuntimeException $e) {
                    $this->addError('resubmit', $e->getMessage());

                    return;
                }

                $this->reset(['product_id', 'supplier_id', 'quantity', 'offered_price', 'payment_terms', 'expires_at', 'resubmit', 'rejectionNotes']);
                $this->successMessage = __('app.offer_resubmitted');

                return;
            }

            $this->resubmit = null;
        }

        PurchaseRequest::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'supplier_id' => $validated['supplier_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'offered_price' => $validated['offered_price'],
            'currency' => $validated['currency'],
            'payment_terms' => $validated['payment_terms'],
            'expires_at' => $validated['expires_at'] ?? null,
            'status' => 'pending',
        ]);

        $this->reset(['product_id', 'supplier_id', 'quantity', 'offered_price', 'payment_terms', 'expires_at']);
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم إرسال عرض الشراء للمراجعة'
            : 'Purchase offer submitted for review';
    }

    public function render()
    {
        $companyId = auth()->user()->company_id;

        return view('livewire.app.submit-purchase-offer', [
            'products' => Product::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name_ar')
                ->limit(50)
                ->get(),
            'suppliers' => Supplier::where('company_id', $companyId)
                ->orderBy('name_ar')
                ->limit(50)
                ->get(),
        ]);
    }
}
