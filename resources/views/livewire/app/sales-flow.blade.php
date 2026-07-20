<div x-data="{ step: $wire.step }">
<div class="main-content">
    <x-page-header :title="__('app.create_invoice')">
        <x-slot:icon><x-heroicon-o-clipboard-document-check width="22" height="22" /></x-slot:icon>
    </x-page-header>

    <div class="page-body" x-effect="step = $wire.step; window.scrollTo(0,0)">
        {{-- Stepper --}}
        <div class="stepper">
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
                        <button type="button" wire:click="$set('customerId', 0)" class="text-danger text-sm bg-transparent border-0 cursor-pointer">{{ __('app.change') }}</button>
                    </div>
                @else
                    <input type="text" id="customerSearch" wire:model.live.debounce.300ms="customerSearch" autocomplete="off" class="form-input"
                        placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن عميل…' : 'Search customer…' }}">
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
            </div>

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
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2v16H3V4zm4 0h1v16H7V4zm3 0h2v16h-2V4zm4 0h1v16h-1V4zm3 0h2v16h-2V4z"/></svg>
                            {{ $arScan ? 'مسح باركود' : 'Scan barcode' }}
                        </button>
                        <button type="button" x-on:click="manual()" class="btn btn-ghost">
                            {{ $arScan ? 'إدخال يدوي' : 'Enter code' }}
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black flex flex-col items-center justify-center p-4"
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
                                    <input type="number" id="cart-price-{{ $i }}" wire:model.live="cart.{{ $i }}.price" min="0" step="0.01" inputmode="decimal" class="form-input">
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
                                        window.jawlaSync.enqueue('sale', {
                                            customer_id: $wire.customerId,
                                            visit_id: $wire.visitId,
                                            items: ($wire.cart || []).map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.price })),
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
                    <x-heroicon-o-cloud-arrow-up width="36" height="36" stroke-width="2.5" />
                </div>
                <h3 class="success-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ app()->getLocale() === 'ar' ? 'بانتظار المزامنة' : 'Queued to sync' }}</h3>
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
                    <x-heroicon-o-check width="36" height="36" stroke-width="2.5" />
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
