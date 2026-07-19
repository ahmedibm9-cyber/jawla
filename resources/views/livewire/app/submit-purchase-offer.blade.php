<div>
<div class="main-content">
    <x-page-header :title="__('app.purchase_offer')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
        @endif

        @if($resubmit)
            <div class="card mb-4 border-warning" role="status">
                <strong class="block">{{ __('app.editing_rejected_offer') }}</strong>
                @if($rejectionNotes)
                    <small class="text-text-secondary block mt-1">{{ __('app.review_notes') }}: {{ $rejectionNotes }}</small>
                @endif
            </div>
        @endif

        @error('resubmit') <div class="toast toast-error relative top-0 mb-4" role="alert" style="transform:none">{{ $message }}</div> @enderror

        <form wire:submit="submit">
            <div class="form-group">
                <label for="product_id" class="form-label">{{ __('app.product') }}</label>
                <x-ds.autocomplete wire:model="product_id" id="product_id" :options="$products" :placeholder="__('app.search_product')" required />
                @error('product_id') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="supplier_id" class="form-label">{{ __('app.supplier_optional') }}</label>
                <x-ds.autocomplete wire:model="supplier_id" id="supplier_id" :options="$suppliers" :placeholder="__('app.search_supplier')" />
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity" class="form-label">{{ __('app.quantity') }}</label>
                    <input type="number" step="0.001" id="quantity" name="quantity" wire:model="quantity" autocomplete="off" class="form-input">
                    @error('quantity') <small class="form-error">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <label for="offered_price" class="form-label">{{ __('app.offered_price') }}</label>
                    <input type="number" step="0.01" id="offered_price" name="offered_price" wire:model="offered_price" autocomplete="off" class="form-input">
                    @error('offered_price') <small class="form-error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="payment_terms" class="form-label">{{ __('app.payment_terms') }}</label>
                <textarea id="payment_terms" wire:model="payment_terms" rows="2" autocomplete="off" class="form-textarea"></textarea>
            </div>

            <div class="form-group">
                <label for="expires_at" class="form-label">{{ __('app.expires_at') }} <span class="text-text-muted">({{ __('app.optional') }})</span></label>
                <input type="date" id="expires_at" wire:model="expires_at" min="{{ now()->toDateString() }}" class="form-input">
                @error('expires_at') <small class="form-error">{{ $message }}</small> @enderror
            </div>

            <x-ds.modal :title="__('app.confirm_purchase_title') ?? 'Submit offer?'" :message="__('app.confirm_purchase_msg') ?? 'This purchase offer will be submitted to the sales manager for review.'">
                <x-slot:trigger>
                    <button type="button" class="btn btn-primary w-full">
                        <span wire:loading.remove>{{ __('app.submit') }}</span>
                        <span wire:loading>{{ __('app.sending') }}&hellip;</span>
                    </button>
                </x-slot:trigger>
                <x-slot:confirm>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.confirm') }}</button>
                </x-slot:confirm>
            </x-ds.modal>
        </form>
    </div>
</div>

<x-tab-bar active="more" />
</div>
