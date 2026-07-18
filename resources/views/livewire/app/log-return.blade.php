<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ __('app.log_return') }}</h2>

    @if($success)
        <div class="card text-center p-6 bg-green-50 border-2 border-success mb-4" aria-live="polite">
            <svg aria-hidden="true" class="size-12 text-success mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <p class="font-bold text-green-700 my-2">{{ $successMessage }}</p>
            <button class="btn btn-outline mt-2" wire:click="$set('success', false)">{{ __('app.log_return') }}</button>
        </div>
    @endif

    <form wire:submit="submit">
        <div class="card">
            <label for="customer_id" class="font-semibold block mb-1">{{ __('app.customer') }} *</label>
            <select wire:model="customer_id" id="customer_id" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
                <option value="">---</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                @endforeach
            </select>
            @error('customer_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="card">
            <label for="reason" class="font-semibold block mb-1">{{ __('app.return_reason') }}</label>
            <textarea wire:model="reason" id="reason" rows="2" autocomplete="off" class="w-full p-3 border border-border rounded-lg"></textarea>
        </div>

        <div class="card">
            <h3 class="m-0 mb-3 text-sm font-semibold">{{ __('app.items_returned') }}</h3>
            @foreach($items as $i => $item)
                <div class="border border-border rounded-lg p-3 mb-3">
                    <div class="mb-2">
                        <label for="item_{{ $i }}_product" class="font-semibold block mb-1 text-sm">{{ __('app.product') }}</label>
                        <select wire:model="items.{{ $i }}.product_id" id="item_{{ $i }}_product" autocomplete="off" class="w-full p-2 border border-border rounded-lg text-sm">
                            <option value="">---</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" @if(isset($stockLookup[$p->id])) data-stock="{{ $stockLookup[$p->id] }}" @endif>{{ $p->name_ar }} @if(isset($stockLookup[$p->id]))({{ $stockLookup[$p->id] }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="item_{{ $i }}_qty" class="font-semibold block mb-1 text-sm">{{ __('app.quantity') }}</label>
                            <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model="items.{{ $i }}.quantity" id="item_{{ $i }}_qty" class="w-full p-2 border border-border rounded-lg text-sm">
                        </div>
                        <div>
                            <label for="item_{{ $i }}_price" class="font-semibold block mb-1 text-sm">{{ __('app.unit_price') }}</label>
                            <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model="items.{{ $i }}.unit_price" id="item_{{ $i }}_price" class="w-full p-2 border border-border rounded-lg text-sm">
                        </div>
                    </div>
                    @if(count($items) > 1)
                        <button type="button" class="btn btn-outline text-danger text-sm mt-2" wire:click="removeItem({{ $i }})">{{ __('app.remove') }}</button>
                    @endif
                </div>
            @endforeach
            <button type="button" class="btn btn-outline text-sm" wire:click="addItem">{{ __('app.add_item') }}</button>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">
            <span wire:loading.remove>{{ __('app.log_return') }}</span>
            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
        </button>
    </form>
</div>
