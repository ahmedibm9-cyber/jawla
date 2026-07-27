<div class="main-content">
    <x-page-header :title="__('app.cash_reconciliation')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($success)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif

        <div class="card mb-4">
            <div class="flex justify-between items-center">
                <span class="text-text-secondary">{{ __('app.expected_in_cashbox') }}</span>
                <strong class="text-accent" dir="ltr">{{ number_format($expectedBalance, 2) }}</strong>
            </div>
        </div>

        <form wire:submit="submit">
            <div class="form-group">
                <label for="counted_amount" class="form-label">{{ __('app.counted_amount') }} *</label>
                <input type="number" step="0.01" min="0" inputmode="decimal" autocomplete="off" wire:model="counted_amount" id="counted_amount" class="form-input" @error('counted_amount') aria-invalid="true" aria-describedby="counted_amount-error" @enderror>
                @error('counted_amount') <small id="counted_amount-error" class="form-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">{{ __('app.notes') }} <span class="text-text-muted">({{ __('app.optional') }})</span></label>
                <textarea wire:model="notes" id="notes" rows="2" autocomplete="off" class="form-textarea" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror></textarea>
                @error('notes') <small id="notes-error" class="form-error">{{ $message }}</small> @enderror
            </div>

            <x-ds.modal :title="__('app.confirm_recon_title')" :message="__('app.confirm_recon_msg')">
                <x-slot:trigger>
                    <button type="button" class="btn btn-primary w-full">
                        <span wire:loading.remove>{{ __('app.submit') }}</span>
                        <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                    </button>
                </x-slot:trigger>
                <x-slot:confirm>
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.confirm') }}</button>
                </x-slot:confirm>
            </x-ds.modal>
        </form>

        @if($history->isNotEmpty())
            <div class="mt-6">
                <h3 class="text-sm font-semibold mb-2">{{ __('app.recon_history') }}</h3>
                @foreach($history as $r)
                    <div class="card mb-2">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <small class="text-text-muted block">{{ $r->created_at->format('Y-m-d H:i') }}</small>
                                <span class="text-text-secondary text-sm">{{ __('app.counted_amount') }}: <span dir="ltr">{{ number_format((float) $r->counted_amount, 2) }}</span></span>
                            </div>
                            <div class="text-start shrink-0" dir="ltr">
                                <span class="badge {{ (float) $r->variance === 0.0 ? 'badge-success' : 'badge-warning' }}">
                                    {{ (float) $r->variance > 0 ? '+' : '' }}{{ number_format((float) $r->variance, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-1">
                            <span class="badge {{ $r->status === 'approved' ? 'badge-success' : ($r->status === 'flagged' ? 'badge-danger' : 'badge-warning') }}">
                                {{ __("app.recon_status_{$r->status}") }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
