<div class="main-content">
    <x-page-header :title="__('app.tickets')">
        <x-slot:icon><x-heroicon-o-ticket class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        <div class="flex gap-2 mb-4" role="group" aria-label="{{ __('app.ticket_filter') }}">
            <button type="button" wire:click="$set('statusFilter', 'all')" class="btn {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.all') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'new')" class="btn {{ $statusFilter === 'new' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.ticket_new') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'in_progress')" class="btn {{ $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.ticket_in_progress') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'resolved')" class="btn {{ $statusFilter === 'resolved' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.ticket_resolved') }}
            </button>
        </div>

        <div class="flex gap-2 mb-4">
            <button type="button" wire:click="toggleCreateForm" class="btn btn-primary flex-1">
                {{ $showCreateForm ? __('app.cancel') : __('app.ticket_create') }}
            </button>
            <button type="button" wire:click="toggleViewMode" class="btn btn-secondary">
                {{ $viewMode === 'table' ? __('app.ticket_kanban') : __('app.ticket_table') }}
            </button>
        </div>

        @if($showCreateForm)
            <div class="card mb-4">
                <form wire:submit="createTicket">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">
                            {{ __('app.ticket_title') }}
                            <input type="text" wire:model="newTitle" class="form-input mt-1" required maxlength="255">
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.ticket_description') }}
                            <textarea wire:model="newDescription" class="form-input mt-1" rows="3" required></textarea>
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.ticket_customer') }}
                            <input type="number" wire:model="newCustomerId" class="form-input mt-1" placeholder="{{ __('app.ticket_customer_optional') }}">
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.ticket_priority') }}
                            <select wire:model="newPriority" class="form-input mt-1">
                                <option value="low">{{ __('app.priority_low') }}</option>
                                <option value="medium">{{ __('app.priority_medium') }}</option>
                                <option value="high">{{ __('app.priority_high') }}</option>
                            </select>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('app.save') }}</span>
                        <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                    </button>
                </form>
            </div>
        @endif

        @if($viewMode === 'kanban')
            {{-- Kanban View --}}
            <div class="kanban-board" style="display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;">
                @php
                    $allTickets = \App\Models\Ticket::where('company_id', auth()->user()->company_id)
                        ->where('is_active', true)
                        ->with(['customer', 'assignee'])
                        ->get()
                        ->groupBy('status');
                @endphp

                @foreach(['new' => 'جديد', 'in_progress' => 'قيد المعالجة', 'resolved' => 'تم الحل'] as $status => $label)
                    <div class="kanban-column" style="min-width:260px;flex:1;">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold text-sm">{{ $label }}</h3>
                            <span class="badge badge-info">{{ count($allTickets[$status] ?? []) }}</span>
                        </div>
                        <div class="kanban-list" data-status="{{ $status }}" style="min-height:200px;background:var(--color-bg-secondary,#f1f5f9);border-radius:8px;padding:8px;">
                            @forelse($allTickets[$status] ?? [] as $ticket)
                                <div class="kanban-card card mb-2 cursor-grab" data-ticket-id="{{ $ticket->id }}" style="cursor:grab;">
                                    <h4 class="font-medium text-sm">{{ $ticket->title }}</h4>
                                    <p class="text-xs text-text-muted mt-1 line-clamp-2">{{ $ticket->description }}</p>
                                    @if($ticket->customer)
                                        <p class="text-xs text-text-muted mt-1">{{ l($ticket->customer->name_ar, $ticket->customer->name_en ?? $ticket->customer->name_ar) }}</p>
                                    @endif
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="badge @class([
                                            'badge-danger' => $ticket->priority === 'high',
                                            'badge-warning' => $ticket->priority === 'medium',
                                            'badge-info' => $ticket->priority === 'low',
                                        ]) text-xs">{{ __('app.priority_'.$ticket->priority) }}</span>
                                        <span class="text-xs text-text-muted">{{ $ticket->created_at->format('m/d') }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-text-muted text-center py-4">{{ __('app.no_tickets') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
            <script>
            document.addEventListener('livewire:initialized', () => {
                document.querySelectorAll('.kanban-list').forEach(list => {
                    new Sortable(list, {
                        group: 'tickets',
                        animation: 150,
                        ghostClass: 'opacity-50',
                        onEnd: function(evt) {
                            const ticketId = evt.item.dataset.ticketId;
                            const newStatus = evt.to.dataset.status;
                            if (ticketId && newStatus) {
                                Livewire.dispatch('call', { method: 'transitionStatus', params: [parseInt(ticketId), newStatus] });
                            }
                        }
                    });
                });
            });
            </script>
            @endpush
        @else
            {{-- Table View --}}
            @forelse($tickets as $ticket)
                <article class="card mb-3" wire:key="ticket-{{ $ticket->id }}">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-base">{{ $ticket->title }}</h2>
                            <p class="text-sm text-text-secondary mt-1">{{ $ticket->description }}</p>
                            @if($ticket->customer)
                                <p class="text-sm text-text-muted mt-1">{{ l($ticket->customer->name_ar, $ticket->customer->name_en ?? $ticket->customer->name_ar) }}</p>
                            @endif
                        </div>
                        <span class="badge @class([
                            'badge-success' => $ticket->status === 'resolved',
                            'badge-danger' => $ticket->status === 'closed',
                            'badge-warning' => $ticket->status === 'in_progress',
                            'badge-info' => $ticket->status === 'new',
                            'badge-neutral' => ! in_array($ticket->status, ['resolved', 'closed', 'in_progress', 'new'], true),
                        ])">{{ __('app.ticket_status_'.$ticket->status) }}</span>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3 text-xs text-text-muted">
                        <span>{{ __('app.ticket_priority') }}: {{ __('app.priority_'.$ticket->priority) }}</span>
                        <span>· {{ __('app.ticket_created') }}: {{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                        @if($ticket->assignee)
                            <span>· {{ __('app.ticket_assigned_to') }}: {{ $ticket->assignee->name }}</span>
                        @endif
                    </div>

                    <div class="mt-4 flex gap-2">
                        @if($ticket->status === 'new')
                            <button type="button" wire:click="transitionStatus({{ $ticket->id }}, 'in_progress')" class="btn btn-primary flex-1">
                                {{ __('app.ticket_start') }}
                            </button>
                        @elseif($ticket->status === 'in_progress')
                            <button type="button" wire:click="transitionStatus({{ $ticket->id }}, 'resolved')" class="btn btn-primary flex-1">
                                {{ __('app.ticket_resolve') }}
                            </button>
                        @elseif($ticket->status === 'resolved')
                            <button type="button" wire:click="transitionStatus({{ $ticket->id }}, 'closed')" class="btn btn-secondary flex-1">
                                {{ __('app.ticket_close') }}
                            </button>
                        @endif
                    </div>
                </article>
            @empty
                <x-ds.empty icon="heroicon-o-ticket" :message="__('app.no_tickets')" />
            @endforelse

            {{ $tickets->links() }}
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
