<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ __('app.collect_payment') }}</h2>

    @if($success)
        <div class="card text-center p-6 bg-green-50 border-2 border-success mb-4" aria-live="polite">
            <svg aria-hidden="true" class="size-12 text-success mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <p class="font-bold text-green-700 my-2">{{ $successMessage }}</p>
            <div class="flex flex-col gap-2 mt-3">
                <a href="/app/pdf/receipt/{{ $lastPaymentId }}" target="_blank" class="btn btn-outline">{{ __('app.view_receipt') }}</a>
                <button class="btn btn-outline" wire:click="$set('success', false)">{{ __('app.collect_another') ?? __('app.collect_payment') }}</button>
            </div>
        </div>
    @endif

    <form wire:submit="submit">
        <div class="card">
            <label for="customer_id" class="font-semibold block mb-1">{{ __('app.customer') }} *</label>
            <select wire:model="customer_id" id="customer_id" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
                <option value="">{{ __('app.select') ?? '---' }}</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                @endforeach
            </select>
            @error('customer_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="card">
            <label for="invoice_id" class="font-semibold block mb-1">{{ __('app.invoice') }} <small class="text-text-secondary">({{ __('app.optional') }})</small></label>
            <select wire:model="invoice_id" id="invoice_id" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
                <option value="">---</option>
                @foreach($invoices as $inv)
                    <option value="{{ $inv->id }}">{{ $inv->invoice_number }} — {{ number_format((float) $inv->remaining_amount, 2) }}</option>
                @endforeach
            </select>
        </div>

        <div class="card">
            <label for="amount" class="font-semibold block mb-1">{{ __('app.amount') }} *</label>
            <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model="amount" id="amount" class="w-full p-3 border border-border rounded-lg">
            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="card">
            <label for="method" class="font-semibold block mb-1">{{ __('app.payment_method') }}</label>
            <select wire:model="method" id="method" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
                <option value="cash">{{ __('app.cash') }}</option>
                <option value="cheque">{{ __('app.cheque') }}</option>
                <option value="transfer">{{ __('app.transfer') }}</option>
                <option value="other">{{ __('app.other') }}</option>
            </select>
        </div>

        <div class="card">
            <label for="notes" class="font-semibold block mb-1">{{ __('app.notes') }}</label>
            <textarea wire:model="notes" id="notes" rows="2" autocomplete="off" class="w-full p-3 border border-border rounded-lg"></textarea>
            @error('notes') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">
            <span wire:loading.remove>{{ __('app.collect') }}</span>
            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
        </button>
    </form>
</div>
