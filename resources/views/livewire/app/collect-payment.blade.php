<div class="main-content">
    <x-page-header :title="__('app.collect_payment')">
        <x-slot:icon><x-heroicon-o-banknotes width="22" height="22" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($success)
            <div class="success-screen" aria-live="polite">
                <div class="success-checkmark">
                    <x-heroicon-o-check width="36" height="36" stroke-width="2.5" />
                </div>
                <h3 class="success-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ $successMessage }}</h3>
                @if($printNotice)
                    <small class="text-text-secondary block mb-2" aria-live="polite">{{ $printNotice }}</small>
                @endif
                <div class="success-actions">
                    @if($lastPaymentId)
                        <x-ds.bluetooth-print-button :payload="$paymentPrintPayload" :label="__('app.print_receipt')" />
                        <a href="/app/pdf/receipt/{{ $lastPaymentId }}" target="_blank" class="btn btn-primary no-underline text-center">{{ __('app.view_receipt') }}</a>
                    @endif
                    <button class="btn btn-outline" wire:click="$set('success', false)">{{ __('app.collect_another') }}</button>
                </div>
            </div>
        @else
            <form wire:submit="submit">
                <div class="form-group">
                    <label for="customer_id" class="form-label">{{ __('app.customer') }} *</label>
                    <x-ds.autocomplete wire:model.live="customer_id" id="customer_id" :options="$customers" :placeholder="__('app.search_customer')" required />
                    @error('customer_id') <small id="customer_id-error" class="form-error">{{ $message }}</small> @enderror
                </div>

                @if(in_array($method, ['cheque', 'transfer'], true))
                    <div class="form-group">
                        <label for="reference_number" class="form-label">{{ __('app.collection_reference') }} *</label>
                        <input type="text" wire:model="reference_number" id="reference_number" class="form-input" maxlength="255" required>
                        @error('reference_number') <small class="form-error">{{ $message }}</small> @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">{{ __('app.collection_evidence') }}</label>
                    <livewire:app.photo-capture :max-photos="3" />
                </div>

                <div class="form-group">
                    <label for="invoice_id" class="form-label">{{ __('app.invoice') }} <span class="text-text-muted">({{ __('app.optional') }})</span></label>
                    <select wire:model="invoice_id" id="invoice_id" autocomplete="off" class="form-select">
                        <option value="">---</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->invoice_number }} — {{ number_format((float) $inv->remaining_amount, 2) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount" class="form-label">{{ __('app.amount') }} *</label>
                    <input type="number" step="0.01" min="0.01" inputmode="decimal" autocomplete="off" wire:model="amount" id="amount" class="form-input" @error('amount') aria-invalid="true" aria-describedby="amount-error" @enderror>
                    @error('amount') <small id="amount-error" class="form-error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label for="method" class="form-label">{{ __('app.payment_method') }}</label>
                    <select wire:model="method" id="method" autocomplete="off" class="form-select">
                        <option value="cash">{{ __('app.cash') }}</option>
                        <option value="cheque">{{ __('app.cheque') }}</option>
                        <option value="transfer">{{ __('app.transfer') }}</option>
                        <option value="other">{{ __('app.other') }}</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes" class="form-label">{{ __('app.notes') }}</label>
                    <textarea wire:model="notes" id="notes" rows="2" autocomplete="off" class="form-textarea" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror></textarea>
                    @error('notes') <small id="notes-error" class="form-error">{{ $message }}</small> @enderror
                </div>

                <x-ds.modal :title="__('app.confirm_collect_title')" :message="__('app.confirm_collect_msg')">
                    <x-slot:trigger>
                        <button type="button" class="btn btn-primary w-full">
                            <span wire:loading.remove>{{ __('app.collect') }}</span>
                            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                        </button>
                    </x-slot:trigger>
                    <x-slot:confirm>
                        {{-- Online: submit normally. Offline: queue to the outbox (CG2) and show the queued screen. --}}
                        <button type="button" wire:loading.attr="disabled" class="btn btn-primary w-full"
                            x-data
                            x-on:click="
                                if (navigator.onLine) {
                                    $wire.submit();
                                } else {
                                    await window.jawlaSync.enqueue('collection_submission', {
                                        customer_id: $wire.customer_id,
                                        invoice_id: $wire.invoice_id,
                                        amount: $wire.amount,
                                        method: $wire.method,
                                        reference_number: $wire.reference_number,
                                        notes: $wire.notes,
                                    });
                                    $wire.queueOffline();
                                }
                            ">{{ __('app.confirm') }}</button>
                    </x-slot:confirm>
                </x-ds.modal>
            </form>
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
