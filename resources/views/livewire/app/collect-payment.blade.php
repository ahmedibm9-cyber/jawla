<div class="main-content">
    <x-page-header :title="__('app.collect_payment')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($success)
            <div class="success-screen" aria-live="polite">
                <div class="success-checkmark">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="success-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ $successMessage }}</h3>
                <div class="success-actions">
                    <a href="/app/pdf/receipt/{{ $lastPaymentId }}" target="_blank" class="btn btn-primary no-underline text-center">{{ __('app.view_receipt') }}</a>
                    <button class="btn btn-outline" wire:click="$set('success', false)">{{ __('app.collect_another') ?? __('app.collect_payment') }}</button>
                </div>
            </div>
        @else
            <form wire:submit="submit">
                <div class="form-group">
                    <label for="customer_id" class="form-label">{{ __('app.customer') }} *</label>
                    <select wire:model="customer_id" id="customer_id" autocomplete="off" class="form-select">
                        <option value="">{{ __('app.select') ?? '---' }}</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <small class="form-error">{{ $message }}</small> @enderror
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
                    <input type="number" step="0.01" min="0.01" inputmode="decimal" autocomplete="off" wire:model="amount" id="amount" class="form-input">
                    @error('amount') <small class="form-error">{{ $message }}</small> @enderror
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
                    <textarea wire:model="notes" id="notes" rows="2" autocomplete="off" class="form-textarea"></textarea>
                    @error('notes') <small class="form-error">{{ $message }}</small> @enderror
                </div>

                <x-ds.modal :title="__('app.confirm_collect_title')" :message="__('app.confirm_collect_msg')">
                    <x-slot:trigger>
                        <button type="button" class="btn btn-primary w-full">
                            <span wire:loading.remove>{{ __('app.collect') }}</span>
                            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                        </button>
                    </x-slot:trigger>
                    <x-slot:confirm>
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.confirm') }}</button>
                    </x-slot:confirm>
                </x-ds.modal>
            </form>
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
