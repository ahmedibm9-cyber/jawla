<div>
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
        </div>

        <div wire:loading.delay class="space-y-2 mb-3" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @if($documents->isEmpty())
            <x-ds.empty icon="heroicon-o-document-text" :message="__('app.no_orders_yet')">
                <x-slot:action>
                    <a href="/app/sell" class="btn btn-primary no-underline">{{ __('app.create_invoice') }}</a>
                </x-slot:action>
            </x-ds.empty>
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
                        <span class="badge {{ in_array($status, ['paid', 'submitted', 'sent', 'converted_to_invoice'], true) ? 'bg-green-100 text-green-700' : (in_array($status, ['cancelled', 'amended'], true) ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ __("app.status_{$status}") }}
                        </span>
                        <span class="flex-1"></span>
                        <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-outline text-sm no-underline">{{ __('app.view_pdf') }}</a>
                        <a href="https://wa.me/?text={{ urlencode($shareText) }}" target="_blank" rel="noopener"
                           class="btn text-sm no-underline text-white" style="background:#25D366"
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
</div>
