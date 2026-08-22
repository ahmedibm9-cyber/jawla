<div x-data="{ step: $wire.step }">
<div class="main-content">
    <x-page-header :title="__('app.create_invoice')">
        <x-slot:icon><x-heroicon-o-clipboard-document-check width="22" height="22" /></x-slot:icon>
    </x-page-header>

    <div class="page-body" x-effect="step = $wire.step; window.scrollTo(0,0)">
        {{-- Stepper --}}
        <div class="stepper" role="list" aria-label="{{ l('خطوات', 'Steps') }}">
            <div class="step {{ $step === 'cart' ? 'active' : 'done' }}">
                <div class="step-dot">{{ $step === 'cart' ? '1' : '&#10003;' }}</div>
                <small>{{ __('app.cart') }}</small>
            </div>
            <div class="step-line {{ $step === 'done' ? 'done' : '' }}"></div>
            <div class="step {{ $step === 'done' ? 'done' : '' }}">
                <div class="step-dot">{{ $step === 'done' ? '&#10003;' : '2' }}</div>
                <small>{{ __('app.done') }}</small>
            </div>
        </div>

        @if($errorMessage)
            <div class="card bg-danger/10 text-danger mb-4 flex justify-between items-center" aria-live="polite">
                <span>{{ $errorMessage }}</span>
                <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="text-danger bg-transparent border-0 cursor-pointer text-lg px-2">&times;</button>
            </div>
        @endif

        @if($step === 'cart')
            {{-- Customer selection --}}
            <div class="form-group">
                <label for="customerSearch" class="form-label">{{ __('app.customer') }}</label>
                @if($selectedCustomer)
                    <div class="card flex justify-between items-center">
                        <span>{{ $selectedCustomer->name_ar }}</span>
                        <button type="button" wire:click="clearCustomer()" class="text-danger text-sm bg-transparent border-0 cursor-pointer">{{ __('app.change') }}</button>
                    </div>
                @else
                    <input type="text" id="customerSearch" wire:model.live.debounce.300ms="customerSearch" autocomplete="off" class="form-input"
                        placeholder="{{ __('app.search_customer_ph') }}">
                    @if($customers->isNotEmpty())
                        <div class="mt-2 space-y-1">
                            @foreach($customers as $c)
                                <button type="button" wire:click="selectCustomer({{ $c->id }})"
                                    class="w-full text-start p-3 rounded-lg border border-border bg-surface hover:bg-surface-hover cursor-pointer">
                                    <strong class="block">{{ $c->name_ar }}</strong>
                                    <small class="text-text-secondary">{{ $c->phone }}</small>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif

                {{-- Customer last-visit preview --}}
                @if($selectedCustomer)
                <div class="card bg-surface-alt mb-4 p-4" aria-label="{{ $selectedCustomer->name_ar ? l('معاينة العميل', 'Customer preview') : '' }}">
                    <div class="flex items-start gap-3">
                        <strong class="text-sm capitalize">{{ $selectedCustomer->name_ar }}</strong>
                        @if($lastVisitAt)
                        <div class="flex-1">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="mr-2 opacity-70"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54.504 1.867-1.084 1.867-2.5a5.97 5.97 0 00-.793-5.388l-2.426-2.15a5.924 5.924 0 00-2.066.076 5.919 5.919 0 00-.808 4.128L12 21a5.917 5.917 0 00.46-3.47l1.957-.758c1.074-.377 2.324-1.06 2.324-2.5C15.9 10.713 13.528 7.5 9 7.5a5.986 5.986 0 00-3.031.06l-1.806.368Zm0-2a3 3 0 110-6 3 3 0 010 6z"/></svg>
                            <span class="text-text-secondary text-xs block">{{ $lastVisitAt->format('M j, Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Product search --}}
            <div class="form-group">
                <label for="productSearch" class="form-label">{{ __('app.products') }}</label>
                <input type="text" id="productSearch" wire:model.live.debounce.300ms="productSearch" autocomplete="off" class="form-input"
                    placeholder="{{ __('app.search_product') }}">

                {{-- Barcode scan (BarcodeDetector API) with a manual-entry fallback --}}
                @php($arScan = app()->getLocale() === 'ar')
                <div class="mt-2" x-data="{
                    open: false,
                    supported: (typeof window.BarcodeDetector !== 'undefined'),
                    stream: null, detector: null, raf: null,
                    async start() {
                        if (!this.supported || !navigator.mediaDevices) { this.manual(); return; }
                        try {
                            this.detector = new window.BarcodeDetector({ formats: ['ean_13','ean_8','upc_a','upc_e','code_128','code_39','qr_code'] });
                            this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                            this.open = true;
                            await this.$nextTick();
                            this.$refs.video.srcObject = this.stream;
                            await this.$refs.video.play();
                            this.loop();
                        } catch (e) { this.stop(); this.manual(); }
                    },
                    async loop() {
                        if (!this.open) return;
                        try {
                            const codes = await this.detector.detect(this.$refs.video);
                            if (codes && codes.length) { const v = codes[0].rawValue; this.stop(); this.$wire.scanBarcode(v); return; }
                        } catch (e) {}
                        this.raf = requestAnimationFrame(() => this.loop());
                    },
                    manual() {
                        const v = window.prompt(@js($arScan ? 'أدخل الباركود' : 'Enter barcode'));
                        if (v) this.$wire.scanBarcode(v);
                    },
                    stop() {
                        this.open = false;
                        if (this.raf) cancelAnimationFrame(this.raf);
                        if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                    },
                }">
                    <div class="flex gap-2">
                        <button type="button" wire:loading.attr="disabled" x-on:click="start()"
                            class="btn btn-secondary flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2v16H3V4zm4 0h1v16H7V4zm3 0h2v16h-2V4zm4 0h1v16h-1V4zm3 0h2v16h-2V4z"/></svg>
                            {{ $arScan ? 'مسح باركود' : 'Scan barcode' }}
                        </button>
                        <button type="button" x-on:click="manual()" class="btn btn-ghost">
                            {{ $arScan ? 'إدخال يدوي' : 'Enter code' }}
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black flex flex-col items-center justify-center p-4"
                        role="dialog" aria-modal="true" aria-label="{{ $arScan ? 'مسح الباركود' : 'Scan barcode' }}"
                        x-on:keydown.escape.window="stop()">
                        <video x-ref="video" playsinline muted class="w-full max-h-[70vh] rounded-lg object-cover"></video>
                        <p class="text-white text-sm mt-3">{{ $arScan ? 'وجّه الكاميرا نحو الباركود' : 'Point the camera at the barcode' }}</p>
                        <button type="button" x-on:click="stop()" class="btn btn-secondary mt-4">{{ $arScan ? 'إلغاء' : 'Cancel' }}</button>
                    </div>
                </div>

                <div wire:loading.delay wire:target="productSearch" class="space-y-1 mt-2" aria-hidden="true">
                    <x-ds.skeleton height="56px" />
                    <x-ds.skeleton height="56px" />
                </div>
                @if($products->isNotEmpty())
                    <div class="mt-2 space-y-1">
                        @foreach($products as $p)
                            <button type="button" wire:click="addToCart({{ $p->id }})"
                                class="w-full text-start p-3 rounded-lg border border-border bg-surface hover:bg-surface-hover cursor-pointer flex justify-between items-center">
                                <div>
                                    <strong class="block">{{ $p->name_ar }}</strong>
                                    <small class="text-text-secondary">{{ $p->sku }}</small>
                                </div>
                                <span class="text-accent font-semibold">{{ number_format((float) $p->price, 2) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Cart items --}}
            @if(!empty($cart))
                <div class="mb-4">
                    <h3 class="text-sm font-semibold mb-2">{{ __('app.cart') }} ({{ count($cart) }})</h3>
                    @foreach($cart as $i => $item)
                        <div class="card">
                            <div class="flex justify-between items-start mb-2 min-w-0">
                                <div>
                                    <strong class="block text-sm">{{ $item['name_ar'] }}</strong>
                                    <small class="text-text-secondary">{{ $item['sku'] }}</small>
                                </div>
                                <button type="button" wire:click="removeItem({{ $i }})" class="text-danger bg-transparent border-0 cursor-pointer text-sm" aria-label="{{ __('app.remove') }}">&times;</button>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cart-qty-{{ $i }}" class="form-label">{{ __('app.quantity') }}</label>
                                    <input type="number" id="cart-qty-{{ $i }}" wire:model.live="cart.{{ $i }}.quantity" min="0.001" step="0.001" inputmode="decimal" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="cart-price-{{ $i }}" class="form-label">{{ __('app.price') }}</label>
                                    <output id="cart-price-{{ $i }}" class="form-input" aria-live="polite">
                                        {{ number_format($item['price'], 2) }}
                                    </output>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="text-xs text-text-secondary">{{ __('app.total') }}</span>
                                <span class="block font-semibold">{{ number_format($item['line_total'] ?? ($item['quantity'] * $item['price']), 2) }}</span>
                            </div>
                        </div>
                    @endforeach

                    {{-- Cart Summary --}}
                    <div class="card bg-surface-alt">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-text-secondary">{{ __('app.subtotal') ?? 'Subtotal' }}</span>
                            <span class="font-medium">{{ number_format($cartSubtotal, 2) }}</span>
                        </div>
                        @if($cartVatAmount > 0)
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-text-secondary">{{ __('app.vat') ?? 'VAT' }}</span>
                                <span class="font-medium">{{ number_format($cartVatAmount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold border-t border-border-light pt-2 mt-2">
                            <span>{{ __('app.grand_total') ?? 'Grand Total' }}</span>
                            <span class="text-accent">{{ number_format($cartTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($selectedCustomer && !empty($cart))
                    <x-ds.modal :title="__('app.confirm_invoice_title')" :message="__('app.confirm_invoice_msg', ['total' => number_format($cartTotal, 2)])">
                        <x-slot:trigger>
                            <button type="button" class="btn btn-primary w-full">{{ __('app.submit') }}</button>
                        </x-slot:trigger>
                        <x-slot:confirm>
                            {{-- Online: submit normally. Offline: queue to the outbox (CG2) and show the queued screen. --}}
                            <button type="button" wire:loading.attr="disabled" class="btn btn-primary w-full"
                                x-data
                                x-on:click="
                                    if (navigator.onLine) {
                                        $wire.submit();
                                    } else {
                                        await window.jawlaSync.enqueue('sale', {
                                            customer_id: $wire.customerId,
                                            visit_id: $wire.visitId,
                                            items: ($wire.cart || []).map(i => ({ product_id: i.product_id, quantity: i.quantity })),
                                        });
                                        $wire.queueOffline();
                                    }
                                ">{{ __('app.confirm') }}</button>
                        </x-slot:confirm>
                    </x-ds.modal>
                @endif
            @endif
        @endif

        @if($step === 'queued')
            <div class="success-screen">
                <div class="success-checkmark" style="background:var(--color-warning,#B45309)">
                    <x-heroicon-o-cloud-arrow-up width="36" height="36" stroke-width="2.5" aria-hidden="true" />
                </div>
                <h3 class="success-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ l('بانتظار المزامنة', 'Queued to sync') }}</h3>
                <p class="success-message">{{ $successMessage }}</p>
                <div class="success-actions">
                    <a href="/app/sell" class="btn btn-primary w-full no-underline text-center">{{ __('app.new_invoice') }}</a>
                    <a href="/app" class="btn w-full no-underline text-center text-text-secondary">{{ __('app.home') }}</a>
                </div>
            </div>
        @endif

        @if($step === 'done')
            <div class="success-screen">
                <div class="success-checkmark">
                    <x-heroicon-o-check width="36" height="36" stroke-width="2.5" aria-hidden="true" />
                </div>
                <h3 class="success-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ __('app.invoice_created') }}</h3>
                <p class="success-message">{{ $successMessage }}</p>
                @if($printNotice)
                    <small class="text-text-secondary block mb-2" aria-live="polite">{{ $printNotice }}</small>
                @endif
                <div class="success-actions">
                    <x-ds.bluetooth-print-button :payload="$invoicePrintPayload" :label="__('app.print_invoice')" />
                    <a href="/app/pdf/invoice/{{ $createdInvoiceId }}" class="btn btn-primary w-full no-underline text-center" target="_blank">
                        {{ __('app.view_pdf') }}
                    </a>
                    <a href="/app/sell" class="btn btn-outline w-full no-underline text-center">
                        {{ __('app.new_invoice') }}
                    </a>
                    <a href="/app" class="btn w-full no-underline text-center text-text-secondary">
                        {{ __('app.home') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<x-tab-bar active="home" />
</div>
