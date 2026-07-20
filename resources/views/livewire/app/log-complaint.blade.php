<div>
<div class="main-content">
    <x-page-header :title="__('app.log_complaint')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <div class="toast toast-success relative top-0 mb-4" aria-live="polite" style="transform:none">{{ $successMessage }}</div>
        @endif

        <form wire:submit="submit">
            <div class="form-group">
                <label for="customer_id" class="form-label">{{ __('app.customer') }} *</label>
                <x-ds.autocomplete wire:model="customer_id" id="customer_id" :options="$customers" :placeholder="__('app.search_customer')" required @error('customer_id') aria-invalid="true" aria-describedby="customer_id-error" @enderror />
                @error('customer_id') <small id="customer_id-error" class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="complaint_type" class="form-label">{{ __('app.complaint_type') }}</label>
                <select id="complaint_type" wire:model="complaint_type" class="form-select">
                    <option value="non_conforming_materials">{{ __('app.type_non_conforming_materials') }}</option>
                    <option value="delivery_issue">{{ __('app.type_delivery_issue') }}</option>
                    <option value="quality_issue">{{ __('app.type_quality_issue') }}</option>
                    <option value="pricing_issue">{{ __('app.type_pricing_issue') }}</option>
                    <option value="other">{{ __('app.type_other') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">{{ __('app.description') }} *</label>
                <textarea id="description" wire:model="description" rows="3" autocomplete="off" class="form-textarea" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror></textarea>
                @error('description') <small id="description-error" class="form-error">{{ $message }}</small> @enderror
            </div>

            <x-ds.modal :title="__('app.confirm_complaint_title') ?? 'Log complaint?'" :message="__('app.confirm_complaint_msg') ?? 'This complaint will be recorded and sent to the sales manager for resolution.'">
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

<x-tab-bar active="home" />
</div>
