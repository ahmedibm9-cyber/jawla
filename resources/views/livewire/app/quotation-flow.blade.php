<div>
<div class="main-content" style="padding:16px">
    <h2 style="margin:0 0 16px">{{ __('app.quotations') }}</h2>

    @if($errorMessage)
        <div class="card" aria-live="polite" style="background:#FEF2F2;color:#DC2626;margin-bottom:12px">{{ $errorMessage }}</div>
    @endif

    @if($successMessage)
        <div class="card" aria-live="polite" style="background:#DCFCE7;color:#15803D;margin-bottom:12px">{{ $successMessage }}</div>
    @endif

    {{-- List View --}}
    @if($step === 'list')
        <h4 style="color:#6b7280;margin:0 0 8px">{{ __('app.pending_quotations') }}</h4>
        @forelse($priced as $r)
            <button class="card" style="cursor:pointer;width:100%;text-align:left;border:none" wire:click="selectQuotation({{ $r->id }})">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <strong>{{ $r->product?->name_ar }}</strong>
                        <small style="display:block;color:#6b7280">{{ $r->customer?->name_ar }} &middot; {{ $r->quantity_requested }} {{ $r->product?->unit ?? '' }}</small>
                    </div>
                    @if($r->quotation)
                        <span style="color:#4DB848;font-weight:700">{{ number_format($r->quotation->base_price, 2) }} EGP</span>
                    @endif
                </div>
            </button>
        @empty
            <div class="card" style="text-align:center;padding:24px;color:#9ca3af">{{ __('app.no_quotations') }}</div>
        @endforelse
    @endif

    {{-- Detail / Negotiation View --}}
    @if($step === 'detail' && $request && $quotation)
        <div class="card">
            <h4 style="margin:0 0 8px">{{ $request->product?->name_ar }}</h4>
            <p style="color:#6b7280;margin:0 0 4px">{{ $request->customer?->name_ar }}</p>
            <p style="color:#6b7280;margin:0 0 12px">{{ __('app.quantity') }}: {{ $request->quantity_requested }} {{ $request->product?->unit ?? '' }}</p>

            <div style="background:#F5F5F4;border-radius:8px;padding:12px;margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span>{{ __('app.base_price') }}</span>
                    <strong>{{ number_format((float)$quotation->base_price, 2) }} EGP</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#6b7280">
                    <span>{{ __('app.range') }}</span>
                    <span>{{ number_format($floor, 2) }} – {{ number_format($ceiling, 2) }} EGP</span>
                </div>
            </div>

            <label style="font-weight:600;display:block;margin-bottom:4px">{{ __('app.your_price') }}</label>
            <input type="number" step="0.01" id="negotiatedPrice" name="negotiatedPrice" wire:model.live="negotiatedPrice" autocomplete="off" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:1.1rem">

            <div style="margin-top:12px;display:flex;gap:8px">
                <button class="btn btn-primary" style="flex:1" wire:click="confirmPrice">{{ __('app.confirm_price') }}</button>
                <button class="btn btn-primary" style="flex:1;background:#2C6FB4" wire:click="createProforma">{{ __('app.create_proforma') }}</button>
            </div>
            <button class="btn btn-outline" style="width:100%;margin-top:8px" wire:click="$set('step', 'list')">{{ __('app.back') }}</button>
        </div>
    @endif

    {{-- Proforma View --}}
    @if($step === 'proforma')
        @php $p = session('proforma'); @endphp
        @if($p)
            <div class="card">
                <h4 style="margin:0 0 4px;color:#16A34A">&#10004; {{ __('app.proforma_created') }}</h4>
                <p style="margin:0 0 8px;color:#6b7280">#{{ $p->proforma_number }}</p>
                <p style="margin:0">{{ __('app.total') }}: <strong>{{ number_format((float)$p->total, 2) }} EGP</strong></p>

                <a href="https://wa.me/?text={{ urlencode(__('app.proforma_msg') . ' #' . $p->proforma_number . ' - ' . number_format((float)$p->total, 2) . ' EGP') }}"
                   target="_blank" class="btn btn-primary" style="display:flex;align-items:center;gap:8px;margin-top:12px;text-decoration:none;background:#25D366">
                    <svg aria-hidden="true" fill="white" width="20" height="20" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>
                <button class="btn btn-outline" style="width:100%;margin-top:8px" wire:click="$set('step', 'list')">{{ __('app.back') }}</button>
            </div>
        @endif
    @endif
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/quotations" class="tab-item active"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>{{ __('app.quotations') }}</a>
    <a href="/app/more" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>