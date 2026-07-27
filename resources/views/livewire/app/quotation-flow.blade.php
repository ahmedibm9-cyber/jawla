<div>
<div class="main-content">
    <x-page-header :title="__('app.quotations')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">

    @if($errorMessage)
        <div class="card bg-red-50 text-danger dark:bg-red-900/20 dark:text-red-400 mb-3 flex justify-between items-center" aria-live="polite">
            <span>{{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="btn-ghost-close">&times;</button>
        </div>
    @endif

    @if($successMessage)
        <div class="card bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 mb-3" aria-live="polite">{{ $successMessage }}</div>
    @endif

    {{-- List View --}}
    @if($step === 'list')
        <h4 class="text-text-secondary m-0 mb-2">{{ __('app.pending_quotations') }}</h4>

        <div wire:loading.delay wire:target="step" class="space-y-2 mb-3" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @forelse($priced as $r)
            <button type="button" class="card w-full text-start" wire:click="selectQuotation({{ $r->id }})">
                <div class="flex justify-between items-center">
                    <div>
                        <strong>{{ $r->product?->name_ar }}</strong>
                        <small class="block text-text-secondary">{{ $r->customer?->name_ar }} &middot; {{ $r->quantity_requested }} {{ $r->product?->unit ?? '' }}</small>
                    </div>
                    @if($r->quotation)
                        <span class="text-accent font-bold">{{ number_format($r->quotation->base_price, 2) }} EGP</span>
                    @endif
                </div>
            </button>
        @empty
            <x-ds.empty icon="heroicon-o-document-text" :message="__('app.no_quotations')" />
        @endforelse
    @endif

    {{-- Detail / Negotiation View --}}
    @if($step === 'detail' && $request && $quotation)
        <div class="card">
            <h4 class="m-0 mb-2">{{ $request->product?->name_ar }}</h4>
            <p class="text-text-secondary m-0 mb-1">{{ $request->customer?->name_ar }}</p>
            <p class="text-text-secondary m-0 mb-3">{{ __('app.quantity') }}: {{ $request->quantity_requested }} {{ $request->product?->unit ?? '' }}</p>

            <div class="bg-surface-alt rounded-lg p-3 mb-3">
                <div class="flex justify-between mb-1">
                    <span>{{ __('app.base_price') }}</span>
                    <strong>{{ number_format((float)$quotation->base_price, 2) }} EGP</strong>
                </div>
                <div class="flex justify-between text-sm text-text-secondary">
                    <span>{{ __('app.range') }}</span>
                    <span>{{ number_format($floor, 2) }} – {{ number_format($ceiling, 2) }} EGP</span>
                </div>
            </div>

            <label for="negotiatedPrice" class="font-semibold block mb-1">{{ __('app.your_price') }}</label>
            <input type="number" step="0.01" id="negotiatedPrice" name="negotiatedPrice" wire:model.live="negotiatedPrice" autocomplete="off" class="form-input text-lg">

            <div class="mt-3 flex gap-2">
                <x-ds.modal :title="__('app.confirm_price_title') ?? 'Confirm price?'" :message="__('app.confirm_price_msg') ?? 'This price will be confirmed and the customer will be notified.'">
                    <x-slot:trigger>
                        <button type="button" class="btn btn-outline flex-1">{{ __('app.confirm_price') }}</button>
                    </x-slot:trigger>
                    <x-slot:confirm>
                        <button type="button" wire:click="confirmPrice" class="btn btn-primary flex-1">{{ __('app.confirm') }}</button>
                    </x-slot:confirm>
                </x-ds.modal>
                <x-ds.modal :title="__('app.confirm_proforma_title') ?? 'Create proforma?'" :message="__('app.confirm_proforma_msg') ?? 'A proforma invoice will be created with the negotiated price.'">
                    <x-slot:trigger>
                        <button type="button" class="btn btn-primary flex-1">{{ __('app.create_proforma') }}</button>
                    </x-slot:trigger>
                    <x-slot:confirm>
                        <button type="button" wire:click="createProforma" class="btn btn-primary flex-1">{{ __('app.confirm') }}</button>
                    </x-slot:confirm>
                </x-ds.modal>
            </div>
            <button class="btn btn-outline w-full mt-2" wire:click="$set('step', 'list')">{{ __('app.back') }}</button>
        </div>
    @endif

    {{-- Proforma View --}}
    @if($step === 'proforma')
        @php $p = session('proforma'); @endphp
        @if($p)
            <div class="card">
                <h4 class="m-0 mb-1 text-success">&#10004; {{ __('app.proforma_created') }}</h4>
                <p class="m-0 mb-2 text-text-secondary">#{{ $p->proforma_number }}</p>
                <p class="m-0">{{ __('app.total') }}: <strong>{{ number_format((float)$p->total, 2) }} EGP</strong></p>

                <a href="https://wa.me/?text={{ urlencode(__('app.proforma_msg') . ' #' . $p->proforma_number . ' - ' . number_format((float)$p->total, 2) . ' EGP') }}"
                   target="_blank" rel="noopener" class="btn btn-primary flex items-center gap-2 mt-3 no-underline" style="background:var(--color-whatsapp)">
                    <svg aria-hidden="true" fill="white" width="20" height="20" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
            <button class="btn btn-outline w-full mt-2" wire:click="$set('step', 'list')">{{ __('app.back') }}</button>
            </div>
        @endif
    @endif
    </div>
</div>

<x-tab-bar active="more" />
</div>