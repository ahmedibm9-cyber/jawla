<div>
<div class="main-content p-4">
    <h2 class="m-0 mb-4">{{ __('app.create_invoice') }}</h2>

    <div class="flex gap-2 mb-4 text-sm">
        <span class="{{ $step === 'cart' ? 'font-bold text-accent' : 'text-text-muted' }}">{{ __('app.cart') }}</span>
        <span class="text-text-muted">&rarr;</span>
        <span class="{{ $step === 'done' ? 'font-bold text-accent' : 'text-text-muted' }}">{{ __('app.done') }}</span>
    </div>

    @if($errorMessage)
        <div class="bg-danger/10 text-danger p-3 rounded-lg mb-4">{{ $errorMessage }}</div>
    @endif

    @if($step === 'cart')
        {{-- Customer selection --}}
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">{{ __('app.customer') }}</label>
            @if($selectedCustomer)
                <div class="card flex justify-between items-center">
                    <span>{{ $selectedCustomer->name_ar }}</span>
                    <button type="button" wire:click="$set('customerId', 0)" class="text-danger text-sm bg-transparent border-0 cursor-pointer">{{ __('app.change') }}</button>
                </div>
            @else
                <input type="text" wire:model.live.debounce.300ms="customerSearch" class="w-full p-3 border border-border rounded-xl text-base"
                    placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن عميل…' : 'Search customer…' }}">
                @if($customers->isNotEmpty())
                    <div class="mt-2 space-y-1">
                        @foreach($customers as $c)
                            <button type="button" wire:click="selectCustomer({{ $c->id }})"
                                class="w-full text-left p-3 rounded-lg border border-border bg-white hover:bg-surface-hover cursor-pointer">
                                <strong class="block">{{ $c->name_ar }}</strong>
                                <small class="text-text-secondary">{{ $c->phone }}</small>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        {{-- Product search --}}
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-1">{{ __('app.products') }}</label>
            <input type="text" wire:model.live.debounce.300ms="productSearch" class="w-full p-3 border border-border rounded-xl text-base"
                placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن منتج…' : 'Search product…' }}">
            @if($products->isNotEmpty())
                <div class="mt-2 space-y-1">
                    @foreach($products as $p)
                        <button type="button" wire:click="addToCart({{ $p->id }})"
                            class="w-full text-left p-3 rounded-lg border border-border bg-white hover:bg-surface-hover cursor-pointer flex justify-between items-center">
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
            <div class="space-y-2 mb-4">
                <h3 class="text-sm font-semibold">{{ __('app.cart') }} ({{ count($cart) }})</h3>
                @foreach($cart as $i => $item)
                    <div class="card">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <strong class="block text-sm">{{ $item['name_ar'] }}</strong>
                                <small class="text-text-secondary">{{ $item['sku'] }}</small>
                            </div>
                            <button type="button" wire:click="removeItem({{ $i }})" class="text-danger bg-transparent border-0 cursor-pointer text-sm">&times;</button>
                        </div>
                        <div class="flex gap-2 items-center text-sm">
                            <div>
                                <label class="text-xs text-text-secondary">{{ __('app.quantity') }}</label>
                                <input type="number" wire:model.live="cart.{{ $i }}.quantity" min="0.001" step="0.001"
                                    class="w-20 p-2 border border-border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-text-secondary">{{ __('app.price') }}</label>
                                <input type="number" wire:model.live="cart.{{ $i }}.price" min="0" step="0.01"
                                    class="w-24 p-2 border border-border rounded-lg text-sm">
                            </div>
                            <div class="ml-auto text-right">
                                <span class="text-xs text-text-secondary">{{ __('app.total') }}</span>
                                <span class="block font-semibold">{{ number_format($item['quantity'] * $item['price'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($selectedCustomer && !empty($cart))
                <button type="button" wire:click="submit" class="btn btn-primary w-full">
                    {{ __('app.submit') }}
                </button>
            @endif
        @endif
    @endif

    @if($step === 'done')
        <div class="card text-center p-8">
            <div class="text-success text-4xl mb-3">&#10003;</div>
            <h3 class="m-0 mb-2">{{ __('app.invoice_created') }}</h3>
            <p class="text-text-secondary mb-4">{{ $successMessage }}</p>
            <a href="/app/pdf/invoice/{{ $createdInvoiceId }}" class="btn btn-primary w-full mb-2" target="_blank">
                {{ __('app.view_pdf') }}
            </a>
            <a href="/app/sell" class="btn w-full block text-center no-underline text-inherit border border-border rounded-xl p-3">
                {{ __('app.new_invoice') }}
            </a>
            <a href="/app" class="btn w-full block text-center no-underline text-inherit mt-2">
                {{ __('app.home') }}
            </a>
        </div>
    @endif
</div>

<nav class="tab-bar" aria-label="Bottom navigation">
    <a href="/app" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>{{ __('app.home') }}</a>
    <a href="/app/customers" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>{{ __('app.customers') }}</a>
    <a href="/app/stock" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>{{ __('app.stock') }}</a>
    <a href="/app/more" class="tab-item"><svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>{{ __('app.more') }}</a>
</nav>
</div>
