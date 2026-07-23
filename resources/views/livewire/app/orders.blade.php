<div>
<x-pull-to-refresh>
<div class="main-content">
    <x-page-header :title="__('app.orders')" />

    <div class="page-body">
        {{-- Type toggle --}}
        <div class="flex gap-2 mb-4" role="tablist" aria-label="{{ __('app.orders') }}">
            <button type="button" role="tab" aria-selected="{{ $type === 'invoices' ? 'true' : 'false' }}"
                class="btn flex-1 {{ $type === 'invoices' ? 'btn-primary' : 'btn-outline' }}"
                wire:click="setType('invoices')">
                {{ __('app.invoices') }}
            </button>
            <button type="button" role="tab" aria-selected="{{ $type === 'proformas' ? 'true' : 'false' }}"
                class="btn flex-1 {{ $type === 'proformas' ? 'btn-primary' : 'btn-outline' }}"
                wire:click="setType('proformas')">
                {{ __('app.proformas') }}
            </button>
            <button type="button" role="tab" aria-selected="{{ $type === 'offers' ? 'true' : 'false' }}"
                class="btn flex-1 {{ $type === 'offers' ? 'btn-primary' : 'btn-outline' }}"
                wire:click="setType('offers')">
                {{ __('app.offers') }}
            </button>
        </div>

        <div wire:loading.delay class="space-y-2 mb-3" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @if($documents->isEmpty())
            @if($type === 'offers')
                <x-ds.empty icon="heroicon-o-shopping-cart" :message="__('app.no_offers_yet')">
                    <x-slot:action>
                        <a href="/app/purchase-offer" class="btn btn-primary no-underline">{{ __('app.submit_purchase_offer') }}</a>
                    </x-slot:action>
                </x-ds.empty>
            @else
                <x-ds.empty icon="heroicon-o-document-text" :message="__('app.no_orders_yet')">
                    <x-slot:action>
                        <a href="/app/sell" class="btn btn-primary no-underline">{{ __('app.create_invoice') }}</a>
                    </x-slot:action>
                </x-ds.empty>
            @endif
        @elseif($type === 'offers')
            @foreach($documents as $offer)
                @php
                    $offerRejected = in_array($offer->status, ['rejected_by_sales', 'rejected_by_purchasing'], true);
                    $offerBadge = match($offer->status) {
                        'pending' => 'badge-warning',
                        'sales_approved' => 'badge badge-info',
                        'purchasing_approved' => 'badge-success',
                        default => 'badge-danger',
                    };
                    $offerNotes = $offer->status === 'rejected_by_sales' ? $offer->sales_review_notes : ($offer->status === 'rejected_by_purchasing' ? $offer->purchasing_review_notes : null);
                @endphp
                <div class="card mb-2">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <strong class="block truncate">{{ $offer->product?->name_ar }}</strong>
                            <small class="text-text-secondary block truncate">{{ $offer->supplier?->name_ar ?? $offer->supplier?->name_en ?? __('app.na') }}</small>
                            <small class="text-text-muted">{{ $offer->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                        <div class="text-start shrink-0" dir="ltr">
                            <strong class="block text-accent">{{ number_format((float) $offer->offered_price, 2) }} {{ $offer->currency }}</strong>
                            <small class="text-text-muted">× {{ rtrim(rtrim(number_format((float) $offer->quantity, 3), '0'), '.') }}</small>
                        </div>
                    </div>

                    @if($offer->status === 'purchasing_approved' && $offer->purchaseOrder)
                        <small class="text-text-secondary block mt-1">{{ __('app.po_number') }}: <span dir="ltr">{{ $offer->purchaseOrder->order_number }}</span></small>
                    @endif

                    @if($offerNotes)
                        <small class="text-text-secondary block mt-1">{{ __('app.review_notes') }}: <span class="line-clamp-2">{{ $offerNotes }}</span></small>
                    @endif

                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-surface-hover">
                        <span class="badge {{ $offerBadge }}">{{ __("app.status_{$offer->status}") }}</span>
                        @if($offer->isExpired())
                            <span class="badge badge-danger">{{ __('app.expired') }}</span>
                        @elseif($offer->expires_at)
                            <small class="text-text-muted">{{ __('app.expires_at') }}: {{ $offer->expires_at->format('Y-m-d') }}</small>
                        @endif
                        <span class="flex-1"></span>
                        @if($offerRejected)
                            <a href="/app/purchase-offer?resubmit={{ $offer->id }}" class="btn btn-outline text-sm no-underline">{{ __('app.resubmit_offer') }}</a>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        @else
            @foreach($documents as $doc)
                @php
                    $isInvoice = $type === 'invoices';
                    $number = $isInvoice ? $doc->invoice_number : $doc->proforma_number;
                    $status = $isInvoice ? $doc->status?->value : $doc->status;
                    $pdfUrl = $isInvoice ? route('app.pdf.invoice', $doc) : route('app.pdf.proforma', $doc);
                    $shareText = ($isInvoice ? __('app.invoices') : __('app.proforma_msg')).' #'.$number.' - '.number_format((float) $doc->total, 2).' EGP';
                @endphp
                <div class="card mb-2">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <strong class="block">#{{ $number }}</strong>
                            <small class="text-text-secondary block truncate">{{ $doc->customer?->name_ar }}</small>
                            <small class="text-text-muted">{{ $doc->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                        <div class="text-start shrink-0" dir="ltr">
                            <strong class="block text-accent">{{ number_format((float) $doc->total, 2) }} EGP</strong>
                            @if($isInvoice && (float) $doc->remaining_amount > 0)
                                <small class="text-warning">{{ __('app.remaining') }}: {{ number_format((float) $doc->remaining_amount, 2) }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-surface-hover">
                        <span class="badge {{ in_array($status, ['paid', 'submitted', 'sent', 'converted_to_invoice'], true) ? 'badge-success' : (in_array($status, ['cancelled', 'amended'], true) ? 'badge-danger' : 'badge-warning') }}">
                            {{ __("app.status_{$status}") }}
                        </span>
                        <span class="flex-1"></span>
                        <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-outline text-sm no-underline">{{ __('app.view_pdf') }}</a>
                        <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener"
                           class="btn text-sm no-underline text-white" style="background:var(--color-whatsapp)"
                           aria-label="{{ __('app.share_whatsapp') }}">WhatsApp</a>
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>

<x-tab-bar active="orders" />
</x-pull-to-refresh>
</div>
