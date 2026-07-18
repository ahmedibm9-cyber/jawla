<?php

namespace App\Livewire\App;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\ReturnService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LogReturn extends Component
{
    public ?int $customer_id = null;

    public string $reason = '';

    public array $items = [];

    public bool $success = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function submit(): void
    {
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $return = app(ReturnService::class)->create(
            companyId: auth()->user()->company_id,
            userId: auth()->id(),
            customerId: $this->customer_id,
            items: array_map(fn ($item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
            ], $this->items),
            reason: $this->reason,
        );

        $this->success = true;
        $this->successMessage = __('app.return_submitted') . ' — ' . $return->return_number;

        $this->reset(['customer_id', 'reason', 'items']);
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function render()
    {
        $user = auth()->user();

        $customers = Customer::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->limit(100)
            ->get();

        $products = Product::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->limit(100)
            ->get();

        $vanWarehouse = Warehouse::where('user_id', $user->id)
            ->where('type', 'van')->first();

        $stockLookup = [];
        if ($vanWarehouse) {
            $stockLookup = Stock::where('warehouse_id', $vanWarehouse->id)
                ->pluck('quantity', 'product_id')
                ->map(fn ($q) => (float) $q)
                ->toArray();
        }

        return view('livewire.app.log-return', [
            'customers' => $customers,
            'products' => $products,
            'stockLookup' => $stockLookup,
        ]);
    }
}
