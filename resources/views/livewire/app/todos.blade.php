<div class="main-content">
    <x-page-header :title="__('app.todos')">
        <x-slot:icon><x-heroicon-o-check-circle class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        <div class="flex gap-2 mb-4" role="group" aria-label="{{ __('app.todo_filter') }}">
            <button type="button" wire:click="$set('statusFilter', 'new')" class="btn {{ $statusFilter === 'new' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.todo_new') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'done')" class="btn {{ $statusFilter === 'done' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.todo_done') }}
            </button>
        </div>

        <button type="button" wire:click="toggleCreateForm" class="btn btn-primary w-full mb-4">
            {{ $showCreateForm ? __('app.cancel') : __('app.todo_create') }}
        </button>

        @if($showCreateForm)
            <div class="card mb-4">
                <form wire:submit="createTodo">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">
                            {{ __('app.todo_title') }}
                            <input type="text" wire:model="newTitle" class="form-input mt-1" required maxlength="255">
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.todo_description') }}
                            <textarea wire:model="newDescription" class="form-input mt-1" rows="2"></textarea>
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.todo_priority') }}
                            <select wire:model="newPriority" class="form-input mt-1">
                                <option value="low">{{ __('app.priority_low') }}</option>
                                <option value="medium">{{ __('app.priority_medium') }}</option>
                                <option value="high">{{ __('app.priority_high') }}</option>
                            </select>
                        </label>
                        <label class="block text-sm font-medium">
                            {{ __('app.todo_due_date') }}
                            <input type="date" wire:model="newDueDate" class="form-input mt-1" required>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-4" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('app.save') }}</span>
                        <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                    </button>
                </form>
            </div>
        @endif

        @forelse($todos as $todo)
            <article class="card mb-3" wire:key="todo-{{ $todo->id }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-base">{{ $todo->title }}</h2>
                        @if($todo->description)
                            <p class="text-sm text-text-secondary mt-1">{{ $todo->description }}</p>
                        @endif
                    </div>
                    <span class="badge @class([
                        'badge-danger' => $todo->priority === 'high',
                        'badge-warning' => $todo->priority === 'medium',
                        'badge-neutral' => $todo->priority === 'low',
                    ])">{{ __('app.priority_'.$todo->priority) }}</span>
                </div>

                <div class="flex flex-wrap gap-2 mt-3 text-xs text-text-muted">
                    <span>{{ __('app.todo_due') }}: {{ $todo->due_date->format('Y-m-d') }}</span>
                </div>

                @if($todo->status === 'new')
                    <div class="mt-4">
                        <x-ds.modal :title="__('app.todo_complete_title')" :message="__('app.todo_complete_message')">
                            <x-slot:trigger>
                                <button type="button" class="btn btn-primary w-full">{{ __('app.todo_complete') }}</button>
                            </x-slot:trigger>
                            <x-slot:confirm>
                                <button type="button" wire:click="complete({{ $todo->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">
                                    <span wire:loading.remove>{{ __('app.confirm') }}</span>
                                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                                </button>
                            </x-slot:confirm>
                        </x-ds.modal>
                    </div>
                @endif
            </article>
        @empty
            <x-ds.empty icon="heroicon-o-check-circle" :message="__('app.no_todos')" />
        @endforelse

        {{ $todos->links() }}
    </div>

    <x-tab-bar active="more" />
</div>
