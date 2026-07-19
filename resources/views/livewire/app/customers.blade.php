<div>
<div class="main-content">
    <x-page-header :title="__('app.customers')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        <div class="form-group">
            <input type="text" wire:model.live="search" aria-label="{{ __('app.search') }}" autocomplete="off" class="form-input"
                placeholder="{{ __('app.search_customers') }}">
        </div>

        <a href="/app/customers/create" class="btn btn-primary w-full mb-3 text-center no-underline">
            {{ __('app.add_customer') }}
        </a>

        <div wire:loading.delay wire:target="search" class="space-y-2 mb-3" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @if($customers->isEmpty())
            <x-ds.empty icon="heroicon-o-users" :message="__('app.no_customers_found')">
                <x-slot:action>
                    <a href="/app/customers/create" class="btn btn-primary no-underline">{{ __('app.add_customer') }}</a>
                </x-slot:action>
            </x-ds.empty>
        @else
            @forelse($customers as $customer)
                <div class="card min-w-0">
                    <strong class="block">{{ $customer->name_ar }}</strong>
                    <small class="text-text-secondary">{{ $customer->code }} · {{ $customer->phone }}</small>
                    <p class="mt-1 text-sm text-text-muted">{{ $customer->address }}</p>
                    @if($customer->latitude && $customer->longitude)
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $customer->latitude }},{{ $customer->longitude }}"
                           target="_blank" rel="noopener" class="maps-link">
                            <svg aria-hidden="true" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m-6-2l6-3m6 10V9m-6 10V9"/></svg>
                            {{ __('app.directions') }}
                        </a>
                    @endif
                </div>
            @empty
                <x-ds.empty icon="heroicon-o-users" :message="__('app.no_customers_found')">
                    <x-slot:action>
                        <a href="/app/customers/create" class="btn btn-primary no-underline">{{ __('app.add_customer') }}</a>
                    </x-slot:action>
                </x-ds.empty>
            @endforelse
        </div>
    </div>
</div>

<x-tab-bar active="customers" />
</div>
