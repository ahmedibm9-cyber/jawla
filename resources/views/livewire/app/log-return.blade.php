<div class="main-content">
    <x-page-header
        :title="__('app.log_return')"
        :icon="'<svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" width=\"22\" height=\"22\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6\"/></svg>'"
    />

    <div class="page-body">
        @if($success)
            <div class="success-screen" aria-live="polite">
                <div class="success-checkmark">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="success-title">{{ $successMessage }}</h3>
                <div class="success-actions">
                    <button class="btn btn-primary" wire:click="$set('success', false)">{{ __('app.log_return') }}</button>
                </div>
            </div>
        @else
            <form wire:submit="submit">
                <div class="form-group">
                    <label for="customer_id" class="form-label">{{ __('app.customer') }} *</label>
                    <select wire:model="customer_id" id="customer_id" autocomplete="off" class="form-select">
                        <option value="">---</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <small class="form-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">{{ __('app.return_reason') }}</label>
                    <textarea wire:model="reason" id="reason" rows="2" autocomplete="off" class="form-textarea"></textarea>
                </div>

                <div class="card">
                    <h3 class="m-0 mb-3 text-sm font-semibold">{{ __('app.items_returned') }}</h3>
                    @foreach($items as $i => $item)
                        <div class="border border-border rounded-lg p-3 mb-3">
                            <div class="form-group">
                                <label for="item_{{ $i }}_product" class="form-label">{{ __('app.product') }}</label>
                                <select wire:model="items.{{ $i }}.product_id" id="item_{{ $i }}_product" autocomplete="off" class="form-select">
                                    <option value="">---</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" @if(isset($stockLookup[$p->id])) data-stock="{{ $stockLookup[$p->id] }}" @endif>{{ $p->name_ar }} @if(isset($stockLookup[$p->id]))({{ $stockLookup[$p->id] }})@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="item_{{ $i }}_qty" class="form-label">{{ __('app.quantity') }}</label>
                                    <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model="items.{{ $i }}.quantity" id="item_{{ $i }}_qty" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="item_{{ $i }}_price" class="form-label">{{ __('app.unit_price') }}</label>
                                    <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model="items.{{ $i }}.unit_price" id="item_{{ $i }}_price" class="form-input">
                                </div>
                            </div>
                            @if(count($items) > 1)
                                <button type="button" class="btn btn-outline text-danger text-sm mt-2" wire:click="removeItem({{ $i }})">{{ __('app.remove') }}</button>
                            @endif
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-outline text-sm w-full" wire:click="addItem">{{ __('app.add_item') }}</button>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:confirm="{{ app()->getLocale() === 'ar' ? 'سيتم تسجيل المرتجع. هل أنت متأكد؟' : 'Return will be logged. Are you sure?' }}" class="btn btn-primary w-full mt-3">
                    <span wire:loading.remove>{{ __('app.log_return') }}</span>
                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                </button>
            </form>
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
