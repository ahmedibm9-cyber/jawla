<div class="main-content">
    <x-page-header :title="__('app.create_sales_order')" />
    <div class="page-body">
        @if($successMessage)<x-ds.toast type="success" :message="$successMessage" />@endif
        @if($errorMessage)<x-ds.toast type="error" :message="$errorMessage" />@endif

        <form wire:submit="submit">
            <div class="form-group">
                <label class="form-label" for="customer_id">{{ __('app.customer') }} *</label>
                <select wire:model="customer_id" id="customer_id" class="form-select" required>
                    <option value="">{{ __('app.select') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ l($customer->name_ar, $customer->name_en) }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="form-error">{{ $message }}</small>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="requested_delivery_date">{{ __('app.requested_delivery_date') }}</label>
                <input type="date" wire:model="requested_delivery_date" id="requested_delivery_date" class="form-input" min="{{ today()->toDateString() }}">
            </div>

            <h2 class="font-semibold mb-2">{{ __('app.items') }}</h2>
            @foreach($items as $index => $item)
                <div class="card mb-2" wire:key="order-item-{{ $index }}">
                    <select wire:model="items.{{ $index }}.product_id" wire:change="productChanged({{ $index }})" class="form-select" required>
                        <option value="">{{ __('app.select_product') }}</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ l($product->name_ar, $product->name_en) }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <input type="number" wire:model="items.{{ $index }}.quantity" class="form-input" min="0.001" step="0.001" aria-label="{{ __('app.quantity') }}">
                        <input type="number" wire:model="items.{{ $index }}.unit_price" class="form-input" min="0" step="0.01" aria-label="{{ __('app.unit_price') }}">
                    </div>
                    @if(count($items) > 1)
                        <button type="button" wire:click="removeItem({{ $index }})" class="btn btn-outline w-full mt-2">{{ __('app.remove') }}</button>
                    @endif
                </div>
            @endforeach
            <button type="button" wire:click="addItem" class="btn btn-outline w-full mb-4">{{ __('app.add_item') }}</button>

            <div class="form-group">
                <label class="form-label" for="notes">{{ __('app.notes') }}</label>
                <textarea wire:model="notes" id="notes" class="form-textarea" rows="3"></textarea>
            </div>

            <x-ds.modal :title="__('app.sales_order_submit_title')" :message="__('app.sales_order_submit_message')">
                <x-slot:trigger><button type="button" class="btn btn-primary w-full">{{ __('app.submit') }}</button></x-slot:trigger>
                <x-slot:confirm><button type="button" wire:click="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.confirm') }}</button></x-slot:confirm>
            </x-ds.modal>
        </form>
    </div>
    <x-tab-bar active="orders" />
</div>
