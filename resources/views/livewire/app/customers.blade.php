<div>
<x-pull-to-refresh>
<div class="main-content">
    <x-page-header :title="__('app.customers')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        <div class="form-group" x-data x-init="$refs.searchInput.value = ''">
            <input type="text" x-ref="searchInput" wire:model.live="search" aria-label="{{ __('app.search') }}" autocomplete="off" autocorrect="off" autocapitalize="off" class="form-input"
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
                <x-slot:action secondary>
                    <a href="/app/routes" class="btn btn-ghost no-underline text-text-secondary">{{ __('app.set_up_route') }}</a>
                </x-slot:action>
            </x-ds.empty>
        @else
            @foreach($customers as $customer)
                @php
                    $isExpanded = $expandedCustomerId === $customer->id;
                    $statusClass = match($customer->status ?? 'approved') {
                        'approved' => 'badge-success',
                        'pending' => 'badge-warning',
                        'rejected' => 'badge-danger',
                        default => 'badge-info',
                    };
                    $statusLabel = match($customer->status ?? 'approved') {
                        'approved' => l('موافق', 'Approved'),
                        'pending' => l('قيد المراجعة', 'Pending'),
                        'rejected' => l('مرفوض', 'Rejected'),
                        default => $customer->status,
                    };
                @endphp
                <div wire:key="{{ $customer->id }}" class="customer-card card min-w-0">
                    {{-- Main customer info (always visible, clickable) --}}
                    <button type="button"
                            wire:click="toggleCustomerActions({{ $customer->id }})"
                            class="customer-card-header w-full text-start bg-transparent border-0 p-0 cursor-pointer"
                            aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                            aria-controls="customer-actions-{{ $customer->id }}">
                        <div class="customer-card-top">
                            <div class="customer-card-info">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <strong class="customer-card-name">{{ $customer->name_ar }}</strong>
                                    @if($customer->status !== 'approved')
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    @endif
                                </div>
                                <small class="text-text-secondary">{{ $customer->code }} · {{ $customer->phone }}</small>
                            </div>
                        <svg class="customer-card-chevron {{ $isExpanded ? 'rotated' : '' }}" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        </div>
                        @if($customer->address)
                            <p class="mt-1 text-sm text-text-muted line-clamp-2">{{ $customer->address }}</p>
                        @endif
                        <div class="customer-card-meta">
                            @if($customer->invoices_count > 0)
                                <span class="customer-meta-item">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ $customer->invoices_count }}
                                </span>
                            @endif
                            @if($customer->visits_count > 0)
                                <span class="customer-meta-item">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ $customer->visits_count }}
                                </span>
                            @endif
                        </div>
                    </button>

                    {{-- Expanded actions --}}
                    @if($isExpanded)
                        <div class="customer-actions" id="customer-actions-{{ $customer->id }}" role="region" x-data x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }))">
                            <div class="customer-actions-grid">
                                {{-- Create Invoice --}}
                                <a href="/app/sell/{{ $customer->id }}"
                                   class="customer-action-btn customer-action-primary no-underline"
                                   onclick="event.stopPropagation()">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    <span>{{ l('فاتورة جديدة', 'Create Invoice') }}</span>
                                </a>

                                {{-- Log Visit --}}
                                <a href="/app/visits"
                                   class="customer-action-btn customer-action-secondary no-underline"
                                   onclick="event.stopPropagation()">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ l('تسجيل زيارة', 'Log Visit') }}</span>
                                </a>

                                {{-- View Invoices --}}
                                <a href="/app/orders"
                                   class="customer-action-btn customer-action-secondary no-underline"
                                   onclick="event.stopPropagation()">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>{{ l('الفواتير', 'Invoices') }}</span>
                                </a>

                                {{-- View History --}}
                                <a href="/app/visits"
                                   class="customer-action-btn customer-action-ghost no-underline"
                                   onclick="event.stopPropagation()">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>{{ l('السجل', 'History') }}</span>
                                </a>

                                {{-- Directions --}}
                                @if($customer->latitude && $customer->longitude)
                                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $customer->latitude }},{{ $customer->longitude }}"
                                       target="_blank" rel="noopener"
                                       class="customer-action-btn customer-action-ghost no-underline"
                                       onclick="event.stopPropagation()">
                                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m-6-2l6-3m6 10V9m-6 10V9"/></svg>
                                        <span>{{ __('app.directions') }}</span>
                                    </a>
                                @endif
                            </div>

                            {{-- Customer balance info --}}
                            @if($customer->balance != 0)
                                <div class="customer-balance {{ $customer->balance < 0 ? 'text-danger' : 'text-success' }}">
                                    <span class="text-xs text-text-secondary">{{ l('الرصيد', 'Balance') }}</span>
                                    <span class="font-semibold">{{ number_format(abs($customer->balance), 2) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>

<x-tab-bar active="customers" />
</x-pull-to-refresh>
</div>
