<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\Product;
use App\Services\SalesOrderService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreateSalesOrder extends Component
{
    public ?int $customer_id = null;

    public ?string $requested_delivery_date = null;

    public ?string $notes = null;

    /** @var list<array{product_id:string|int, quantity:float|int, unit_price:float|int}> */
    public array $items = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function productChanged(int $index): void
    {
        $product = Product::query()->find($this->items[$index]['product_id'] ?? null);
        if ($product) {
            $this->items[$index]['unit_price'] = (float) $product->price;
        }
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'requested_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $order = app(SalesOrderService::class)->createAndSubmit(
                auth()->user(),
                (int) $validated['customer_id'],
                $validated['items'],
                [
                    'requested_delivery_date' => $validated['requested_delivery_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ],
            );
            $this->successMessage = __('app.sales_order_submitted').' — '.$order->order_number;
            $this->reset(['customer_id', 'requested_delivery_date', 'notes', 'items']);
            $this->addItem();
        } catch (\Throwable $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.app.create-sales-order', [
            'customers' => Customer::query()->where('status', 'approved')->where('is_active', true)->orderBy('name_ar')->limit(100)->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name_ar')->limit(100)->get(),
        ]);
    }
}
