<?php

namespace App\Livewire\App;

use App\Exceptions\Domain\DomainException as AppDomainException;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Contracts\PricingService;
use App\Services\SalesOrderService;
use Illuminate\Auth\Access\AuthorizationException;
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
        $this->refreshItemPrice($index);
    }

    public function quantityChanged(int $index): void
    {
        $this->refreshItemPrice($index);
    }

    public function updatedCustomerId(): void
    {
        foreach (array_keys($this->items) as $index) {
            $this->refreshItemPrice($index);
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
        } catch (AppDomainException|\DomainException|AuthorizationException $exception) {
            $this->errorMessage = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.workflow_failed');
        }
    }

    public function queueOffline(): void
    {
        $this->successMessage = __('app.sales_order_queued_offline');
        $this->reset(['customer_id', 'requested_delivery_date', 'notes', 'items']);
        $this->addItem();
    }

    public function offlineQueueFailed(): void
    {
        $this->errorMessage = __('app.workflow_failed');
    }

    public function render()
    {
        return view('livewire.app.create-sales-order', [
            'customers' => Customer::query()->where('company_id', auth()->user()->activeCompanyId())
                ->where('status', 'approved')->where('is_active', true)->orderBy('name_ar')->limit(100)->get(),
            'products' => Product::query()->where('company_id', auth()->user()->activeCompanyId())
                ->where('is_active', true)->orderBy('name_ar')->limit(100)->get(),
        ]);
    }

    private function refreshItemPrice(int $index): void
    {
        $productId = (int) ($this->items[$index]['product_id'] ?? 0);
        $quantity = number_format((float) ($this->items[$index]['quantity'] ?? 0), 3, '.', '');
        if ($this->customer_id === null || $productId < 1 || bccomp($quantity, '0.000', 3) <= 0) {
            return;
        }

        $this->items[$index]['unit_price'] = (float) app(PricingService::class)->effectivePrice(
            auth()->user()->activeCompanyId(),
            $this->customer_id,
            $productId,
            $quantity,
        );
    }
}
