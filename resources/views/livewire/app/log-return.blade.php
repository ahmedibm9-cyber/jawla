<div class="main-content">
    <x-page-header :title="__('app.log_return')">
        <x-slot:icon><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if ($success)
            <div class="success-screen" aria-live="polite">
                <div class="success-checkmark">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="success-title">{{ $successMessage }}</h3>
                <button class="btn btn-primary" wire:click="$set('success', false)">{{ __('app.log_return') }}</button>
            </div>
        @else
            @if ($errorMessage)
                <div class="alert alert-danger" role="alert">{{ $errorMessage }}</div>
            @endif

            <form wire:submit="submit">
                <div class="form-group">
                    <label for="customer_id" class="form-label">{{ __('app.customer') }} *</label>
                    <x-ds.autocomplete wire:model.live="customer_id" id="customer_id" :placeholder="__('app.search_customer')" required>
                        <option value="">---</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name_ar }}</option>
                        @endforeach
                    </x-ds.autocomplete>
                    @error('customer_id') <small class="form-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="against_invoice_id" class="form-label">{{ app()->getLocale() === 'ar' ? 'الفاتورة الأصلية' : 'Original invoice' }} *</label>
                    <select wire:model.live="against_invoice_id" id="against_invoice_id" class="form-select" required>
                        <option value="">---</option>
                        @foreach ($invoices as $invoice)
                            <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} — {{ $invoice->issued_at?->format('Y-m-d') }}</option>
                        @endforeach
                    </select>
                    @error('against_invoice_id') <small class="form-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">{{ __('app.return_reason') }} *</label>
                    <textarea wire:model="reason" id="reason" rows="2" class="form-textarea" required></textarea>
                </div>

                <div class="card">
                    <h3 class="m-0 mb-3 text-sm font-semibold">{{ __('app.items_returned') }}</h3>
                    @foreach ($items as $index => $item)
                        <div class="border border-border rounded-lg p-3 mb-3">
                            <div class="form-group">
                                <label for="item_{{ $index }}_line" class="form-label">{{ app()->getLocale() === 'ar' ? 'بند الفاتورة' : 'Invoice line' }}</label>
                                <select wire:model="items.{{ $index }}.invoice_item_id" id="item_{{ $index }}_line" class="form-select" required>
                                    <option value="">---</option>
                                    @foreach ($invoiceLines as $line)
                                        <option value="{{ $line->id }}">
                                            {{ $line->product?->name_ar }} — {{ $line->quantity }} × {{ $line->unit_price }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="item_{{ $index }}_qty" class="form-label">{{ __('app.quantity') }}</label>
                                    <input type="number" step="0.001" min="0.001" wire:model="items.{{ $index }}.quantity" id="item_{{ $index }}_qty" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="item_{{ $index }}_condition" class="form-label">{{ app()->getLocale() === 'ar' ? 'الحالة' : 'Condition' }}</label>
                                    <select wire:model="items.{{ $index }}.condition" id="item_{{ $index }}_condition" class="form-select">
                                        <option value="sellable">{{ app()->getLocale() === 'ar' ? 'صالح للبيع' : 'Sellable' }}</option>
                                        <option value="damaged">{{ app()->getLocale() === 'ar' ? 'تالف — إلى الحجر' : 'Damaged — quarantine' }}</option>
                                    </select>
                                </div>
                            </div>
                            @if (count($items) > 1)
                                <button type="button" class="btn btn-outline text-danger text-sm" wire:click="removeItem({{ $index }})">{{ __('app.remove') }}</button>
                            @endif
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-outline text-sm w-full" wire:click="addItem">{{ __('app.add_item') }}</button>
                </div>

                <div class="card mt-3">
                    <livewire:app.photo-capture />
                </div>

                <x-ds.modal class="mt-3" :title="__('app.confirm_return_title')" :message="__('app.confirm_return_msg')">
                    <x-slot:trigger>
                        <button type="button" class="btn btn-primary w-full">{{ __('app.log_return') }}</button>
                    </x-slot:trigger>
                    <x-slot:confirm>
                        <button type="button" wire:loading.attr="disabled" class="btn btn-primary w-full"
                            x-data
                            x-on:click="
                                if (navigator.onLine) {
                                    $wire.submit();
                                } else {
                                    await window.jawlaSync.enqueue('return', {
                                        customer_id: $wire.customer_id,
                                        against_invoice_id: $wire.against_invoice_id,
                                        reason: $wire.reason,
                                        items: ($wire.items || [])
                                            .filter(item => item.invoice_item_id)
                                            .map(item => ({
                                                invoice_item_id: item.invoice_item_id,
                                                quantity: item.quantity,
                                                condition: item.condition,
                                            })),
                                    });
                                    $wire.queueOffline();
                                }
                            "
                        >{{ __('app.confirm') }}</button>
                    </x-slot:confirm>
                </x-ds.modal>
            </form>
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
