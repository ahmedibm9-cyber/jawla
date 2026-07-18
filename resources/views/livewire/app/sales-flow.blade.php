<div>
<div class="main-content">
    <x-page-header
        :title="__('app.create_invoice')"
        :icon="'<svg fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" width=\"22\" height=\"22\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4\"/></svg>'"
    />

    <div class="page-body">
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
            <div class="card bg-danger/10 text-danger mb-4">{{ $errorMessage }}</div>
        @endif

        @if($step === 'cart')
            {{-- Customer selection --}}
            <div class="form-group">
                <label class="form-label">{{ __('app.customer') }}</label>
                @if($selectedCustomer)
                    <div class="card flex justify-between items-center">
                        <span>{{ $selectedCustomer->name_ar }}</span>
                        <button type="button" wire:click="$set('customerId', 0)" class="text-danger text-sm bg-transparent border-0 cursor-pointer">{{ __('app.change') }}</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="customerSearch" class="form-input"
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
            <div class="form-group">
                <label class="form-label">{{ __('app.products') }}</label>
                <input type="text" wire:model.live.debounce.300ms="productSearch" class="form-input"
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
                                    <label class="form-label">{{ __('app.quantity') }}</label>
                                    <input type="number" wire:model.live="cart.{{ $i }}.quantity" min="0.001" step="0.001" inputmode="decimal" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('app.price') }}</label>
                                    <input type="number" wire:model.live="cart.{{ $i }}.price" min="0" step="0.01" inputmode="decimal" class="form-input">
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-text-secondary">{{ __('app.total') }}</span>
                                <span class="block font-semibold">{{ number_format($item['quantity'] * $item['price'], 2) }}</span>
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
            <div class="success-screen">
                <div class="success-checkmark">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="success-title">{{ __('app.invoice_created') }}</h3>
                <p class="success-message">{{ $successMessage }}</p>
                <div class="success-actions">
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
