<?php

namespace App\Livewire\App;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubmitPurchaseOffer extends Component
{
    public ?int $product_id = null;

    public ?int $supplier_id = null;

    public ?float $quantity = null;

    public ?float $offered_price = null;

    public string $currency = 'EGP';

    public string $payment_terms = '';

    public ?string $successMessage = null;

    public function submit(): void
    {
        $validated = $this->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.001',
            'offered_price' => 'required|numeric|min:0.01',
            'currency' => 'required|in:EGP,USD,EUR,SAR',
            'payment_terms' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        PurchaseRequest::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'supplier_id' => $validated['supplier_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'offered_price' => $validated['offered_price'],
            'currency' => $validated['currency'],
            'payment_terms' => $validated['payment_terms'],
            'status' => 'pending',
        ]);

        $this->reset(['product_id', 'supplier_id', 'quantity', 'offered_price', 'payment_terms']);
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
