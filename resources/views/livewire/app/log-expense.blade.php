<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ __('app.log_expense') }}</h2>

    <div class="card bg-amber-50 text-amber-800 mb-4 flex items-center gap-2">
        <svg aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ __('app.you_have') }} <strong>{{ number_format($cashBoxBalance, 2) }}</strong> {{ __('app.in_cashbox') }}</span>
    </div>

    @if($success)
        <div class="card text-center p-6 bg-green-50 border-2 border-success mb-4" aria-live="polite">
            <svg aria-hidden="true" class="size-12 text-success mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <p class="font-bold text-green-700 my-2">{{ $successMessage }}</p>
            <button class="btn btn-outline mt-2" wire:click="$set('success', false)">{{ __('app.log_expense') }}</button>
        </div>
    @endif

    <form wire:submit="submit">
        <div class="card">
            <label for="category" class="font-semibold block mb-1">{{ __('app.expense_category') }} *</label>
            <select wire:model="category" id="category" autocomplete="off" class="w-full p-3 border border-border rounded-lg">
                <option value="fuel">{{ __('app.expense_fuel') }}</option>
                <option value="maintenance">{{ __('app.expense_maintenance') }}</option>
                <option value="food">{{ __('app.expense_food') }}</option>
                <option value="other">{{ __('app.expense_other') }}</option>
            </select>
        </div>

        <div class="card">
            <label for="amount" class="font-semibold block mb-1">{{ __('app.expense_amount') }} *</label>
            <input type="number" step="0.01" inputmode="decimal" autocomplete="off" wire:model="amount" id="amount" class="w-full p-3 border border-border rounded-lg">
            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="card">
            <label for="note" class="font-semibold block mb-1">{{ __('app.expense_note') }}</label>
            <textarea wire:model="note" id="note" rows="2" autocomplete="off" class="w-full p-3 border border-border rounded-lg"></textarea>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">
            <span wire:loading.remove>{{ __('app.log_expense') }}</span>
            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
        </button>
    </form>
</div>
