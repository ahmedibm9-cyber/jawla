<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\Product;
use App\Services\Contracts\InvoiceService;
use App\Support\ThermalPrintFormatter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesFlow extends Component
{
    public string $step = 'cart'; // cart, review, done

    public int $customerId = 0;

    public ?int $visitId = null;

    public string $customerSearch = '';

    public string $productSearch = '';

    public array $cart = [];

    public string $errorMessage = '';

    public ?string $successMessage = null;

    public int $createdInvoiceId = 0;

    public array $invoicePrintPayload = [];

    public ?string $printNotice = null;

    public function mount(?int $customer = null, ?int $visitId = null): void
    {
        if ($customer) {
            $this->customerId = $customer;
        }

        $this->visitId = $visitId;
        $this->recalcCart();
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->customerSearch = '';
        $this->errorMessage = '';
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        foreach ($this->cart as &$item) {
            if ($item['product_id'] === $productId) {
                $item['quantity'] += 1;
                $this->recalcCart();

                return;
            }
        }

        $this->cart[] = [
            'product_id' => $productId,
            'name_ar' => $product->name_ar,
            'name_en' => $product->name_en,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'unit' => $product->unit,
            'quantity' => 1,
            'vat_applicable' => (bool) $product->vat_applicable,
        ];

        $this->recalcCart();
    }

    /**
     * Resolve a scanned/entered code to a product and add it to the cart.
     * Matches the product's barcode first, then falls back to SKU. Company
     * scoping is enforced by the BelongsToCompany global scope.
     */
    public function scanBarcode(string $code): void
    {
        $code = trim($code);
        if ($code === '') {
            return;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        if (! $product) {
            $this->errorMessage = app()->getLocale() === 'ar'
                ? "لا يوجد منتج بهذا الباركود: {$code}"
                : "No product found for barcode: {$code}";

            return;
        }

        $this->errorMessage = '';
        $this->addToCart($product->id);
    }

    public function updateQty(int $index, float $qty): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        if ($qty <= 0) {
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);
        } else {
            // Cap at 9999 to prevent absurd quantities
            $this->cart[$index]['quantity'] = min($qty, 9999);
        }

        $this->recalcCart();
    }

    public function updatePrice(int $index, float $price): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        // Floor at 0.01 — zero-price requires explicit manager approval
        $this->cart[$index]['price'] = max(0.01, $price);
        $this->recalcCart();
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->recalcCart();
    }

    public float $cartSubtotal = 0.0;

    public float $cartVatAmount = 0.0;

    public float $cartTotal = 0.0;

    public function updatedCart(): void
    {
        $this->recalcCart();
    }

    public function recalcCart(): void
    {
        $subtotal = 0.0;
        $vatAmount = 0.0;
        $vatPercent = (float) (auth()->user()?->company?->vat_percent ?? 0);

        if (auth()->user() === null || auth()->user()->company === null) {
            $this->errorMessage = app()->getLocale() === 'ar'
                ? 'حدث خطأ — يرجى إعادة تسجيل الدخول'
                : 'Session error — please log in again';
            return;
        }

        foreach ($this->cart as &$item) {
            $lineTotal = round($item['quantity'] * $item['price'], 2);
            $item['line_total'] = $lineTotal;
            $subtotal += $lineTotal;

            if ($item['vat_applicable'] && $vatPercent > 0) {
                $item['vat_amount'] = round($lineTotal * ($vatPercent / 100), 2);
                $vatAmount += $item['vat_amount'];
            } else {
                $item['vat_amount'] = 0.0;
            }
        }
        unset($item);

        $this->cartSubtotal = round($subtotal, 2);
        $this->cartVatAmount = round($vatAmount, 2);
        $this->cartTotal = round($subtotal + $vatAmount, 2);
    }

    public function submit(InvoiceService $invoices, ThermalPrintFormatter $printer): void
    {
        if ($this->customerId <= 0) {
            $this->errorMessage = app()->getLocale() === 'ar' ? 'اختر عميلاً' : 'Select a customer';

            return;
        }

        if (empty($this->cart)) {
            $this->errorMessage = app()->getLocale() === 'ar' ? 'أضف منتجات إلى الفاتورة' : 'Add products to the invoice';

            return;
        }

        $customer = Customer::find($this->customerId);
        if (! $customer) {
            $this->errorMessage = app()->getLocale() === 'ar' ? 'العميل غير موجود' : 'Customer not found';

            return;
        }

        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ];
        }

        try {
            $invoice = $invoices->create([
                'company_id' => auth()->user()->company_id,
                'customer_id' => $this->customerId,
                'visit_id' => $this->visitId,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->createdInvoiceId = $invoice->id;
        $this->step = 'done';
        $this->successMessage = __('app.invoice_created').' #'.$invoice->invoice_number;

        // Offer a brief undo (B1) — reverses via InvoiceService::cancel.
        $this->dispatch('action-completed', type: 'sale', id: $invoice->id,
            message: __('app.invoice_created').' #'.$invoice->invoice_number);

        try {
            $this->invoicePrintPayload = $printer->invoicePayload($invoice);
        } catch (\Throwable) {
            $this->invoicePrintPayload = [];
            $this->printNotice = app()->getLocale() === 'ar'
                ? 'تم إنشاء الفاتورة لكن تعذر تجهيز بيانات الطباعة. استخدم PDF.'
                : 'Invoice was created, but the print payload could not be prepared. Use the PDF instead.';
        }
    }

    /**
     * Offline path: the client has already enqueued the sale to the outbox
     * (IndexedDB) and will sync it when back online. Show the queued confirmation
     * and clear the cart — no server write happens here.
     */
    public function queueOffline(): void
    {
        $this->step = 'queued';
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم حفظ الفاتورة دون اتصال وستتم مزامنتها تلقائيًا عند عودة الاتصال.'
            : 'Invoice saved offline — it will sync automatically when you are back online.';
        $this->reset(['cart', 'customerId', 'visitId']);
        $this->recalcCart();
    }

    public function render()
    {
        $customers = collect();
        if (strlen($this->customerSearch) >= 2 && $this->customerId <= 0) {
            $customers = Customer::query()
                ->where('company_id', auth()->user()->company_id)
                ->where(function ($q) {
                    $q->where('name_ar', 'ilike', "%{$this->customerSearch}%")
                        ->orWhere('name_en', 'ilike', "%{$this->customerSearch}%")
                        ->orWhere('phone', 'ilike', "%{$this->customerSearch}%");
                })
                ->where('is_active', true)
                ->limit(10)
                ->get();
        }

        $products = collect();
        if (strlen($this->productSearch) >= 2) {
            $products = Product::query()
                ->where('company_id', auth()->user()->company_id)
                ->where(function ($q) {
                    $q->where('sku', 'ilike', "%{$this->productSearch}%")
                        ->orWhere('name_ar', 'ilike', "%{$this->productSearch}%")
                        ->orWhere('name_en', 'ilike', "%{$this->productSearch}%");
                })
                ->where('is_active', true)
                ->limit(10)
                ->get();
        }

        return view('livewire.app.sales-flow', [
            'customers' => $customers,
            'products' => $products,
            'selectedCustomer' => $this->customerId > 0 ? Customer::find($this->customerId) : null,
        ]);
    }
}
