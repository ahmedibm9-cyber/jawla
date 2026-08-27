<div class="main-content">
    <x-page-header :title="__('app.requests')">
        <x-slot:icon><x-heroicon-o-paper-airplane class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        <div class="flex gap-2 mb-4" role="group" aria-label="{{ __('app.request_filter') }}">
            <button type="button" wire:click="$set('statusFilter', 'new')" class="btn {{ $statusFilter === 'new' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.status_new') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'approved')" class="btn {{ $statusFilter === 'approved' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.status_approved') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'all')" class="btn {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.all') }}
            </button>
        </div>

        <div class="flex gap-2 mb-4">
            <button type="button" wire:click="toggleCreateForm" class="btn btn-primary flex-1">
                {{ $showCreateForm ? __('app.cancel') : __('app.request_create') }}
            </button>
            <button type="button" wire:click="toggleView" class="btn btn-secondary">
                {{ $viewMode === 'table' ? 'Kanban' : 'Table' }}
            </button>
        </div>

        @if($showCreateForm)
            <div class="card mb-4">
                <form wire:submit="createRequest">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">
                            {{ __('app.request_type') }}
                            <select wire:model="newType" class="form-input mt-1">
                                <option value="discount">{{ __('app.request_type_discount') }}</option>
                                <option value="leave">{{ __('app.request_type_leave') }}</option>
                                <option value="price_override">{{ __('app.request_type_price_override') }}</option>
                                <option value="other">{{ __('app.request_type_other') }}</option>
                            </select>
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.request_title') }}
                            <input type="text" wire:model="newTitle" class="form-input mt-1" required maxlength="255">
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.request_description') }}
                            <textarea wire:model="newDescription" class="form-input mt-1" rows="3" required maxlength="2000"></textarea>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('app.save') }}</span>
                        <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                    </button>
                </form>
            </div>
        @endif

        @if($viewMode === 'table')
            <div class="card mb-4">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('app.request_type') }}</th>
                                <th>{{ __('app.request_title') }}</th>
                                <th>{{ __('app.status') }}</th>
                                <th>{{ __('app.date') }}</th>
                                <th>{{ __('app.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $request)
                                <tr>
                                    <td>
                                        <span class="badge badge-info">{{ __('app.request_type_'.$request->type) }}</span>
                                    </td>
                                    <td class="font-medium">{{ $request->title }}</td>
                                    <td>
                                        @if($request->status === 'new')
                                            <span class="badge badge-info">{{ __('app.status_new') }}</span>
                                        @elseif($request->status === 'approved')
                                            <span class="badge badge-warning">{{ __('app.status_approved') }}</span>
                                        @elseif($request->status === 'rejected')
                                            <span class="badge badge-danger">{{ __('app.status_rejected') }}</span>
                                        @else
                                            <span class="badge badge-success">{{ __('app.status_done') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-sm text-text-muted">{{ $request->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        @if($request->status === 'new')
                                            <div class="flex gap-1">
                                                <button type="button" wire:click="approveRequest({{ $request->id }})" class="btn btn-sm btn-primary">{{ __('app.approve') }}</button>
                                                <button type="button" wire:click="rejectRequest({{ $request->id }})" class="btn btn-sm btn-danger">{{ __('app.reject') }}</button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-ds.empty icon="heroicon-o-paper-airplane" :message="__('app.no_requests')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                @php
                    $grouped = $requests->groupBy('status');
                @endphp
                @foreach(['new' => 'info', 'approved' => 'warning', 'done' => 'success'] as $status => $color)
                    <div class="card">
                        <h3 class="font-semibold mb-3">{{ __('app.status_'.$status) }}</h3>
                        @forelse($grouped->get($status, collect()) as $request)
                            <div class="border-b border-border py-2 last:border-0">
                                <p class="font-medium text-sm">{{ $request->title }}</p>
                                <p class="text-xs text-text-muted">{{ __('app.request_type_'.$request->type) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-text-muted">{{ __('app.no_items') }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        @endif

        {{ $requests->links() }}
    </div>

    <x-tab-bar active="more" />
</div>
