<div class="main-content">
    <x-page-header :title="__('app.tasks')">
        <x-slot:icon><x-heroicon-o-clipboard-document-check class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        <div class="flex gap-2 mb-4" role="group" aria-label="{{ __('app.task_filter') }}">
            <button type="button" wire:click="$set('statusFilter', 'active')" class="btn {{ $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.task_active') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'completed')" class="btn {{ $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.task_completed') }}
            </button>
        </div>

        @forelse($tasks as $task)
            <article class="card mb-3" wire:key="task-{{ $task->id }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-base">{{ $task->title }}</h2>
                        @if($task->customer)
                            <p class="text-sm text-text-secondary mt-1">{{ l($task->customer->name_ar, $task->customer->name_en ?? $task->customer->name_ar) }}</p>
                        @endif
                    </div>
                    <span class="badge @class([
                        'badge-success' => $task->status === \App\Enums\TaskStatus::Approved,
                        'badge-danger' => in_array($task->status, [\App\Enums\TaskStatus::Rejected, \App\Enums\TaskStatus::Cancelled], true),
                        'badge-warning' => in_array($task->status, [\App\Enums\TaskStatus::ChangesRequested, \App\Enums\TaskStatus::Reopened], true),
                        'badge-info' => $task->status === \App\Enums\TaskStatus::Submitted,
                        'badge-neutral' => ! in_array($task->status, [\App\Enums\TaskStatus::Approved, \App\Enums\TaskStatus::Rejected, \App\Enums\TaskStatus::Cancelled, \App\Enums\TaskStatus::ChangesRequested, \App\Enums\TaskStatus::Reopened, \App\Enums\TaskStatus::Submitted], true),
                    ])">{{ __('app.task_status_'.$task->status->value) }}</span>
                </div>

                @if($task->note)
                    <p class="text-sm mt-3 whitespace-pre-line">{{ $task->note }}</p>
                @endif

                <div class="flex flex-wrap gap-2 mt-3 text-xs text-text-muted">
                    <span>{{ __('app.task_priority') }}: {{ __('app.task_priority_'.$task->priority) }}</span>
                    @if($task->due_date)
                        <span>· {{ __('app.task_due') }}: {{ $task->due_date->format('Y-m-d') }}</span>
                    @endif
                </div>

                @if($task->decision_reason)
                    <div class="mt-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 p-3 text-sm" role="alert">
                        <strong>{{ __('app.task_manager_feedback') }}</strong>
                        <p class="mt-1">{{ $task->decision_reason }}</p>
                    </div>
                @endif

                @if(!empty($task->checklist))
                    <fieldset class="mt-4 space-y-2" @disabled(!in_array($task->status, [\App\Enums\TaskStatus::Accepted, \App\Enums\TaskStatus::InProgress, \App\Enums\TaskStatus::Reopened], true))>
                        <legend class="text-sm font-semibold mb-2">{{ __('app.task_checklist') }}</legend>
                        @foreach($task->checklist as $index => $item)
                            <label class="flex items-start gap-3 text-sm cursor-pointer">
                                <input type="checkbox"
                                       class="mt-0.5"
                                       @checked($item['completed'] ?? false)
                                       wire:click="toggleChecklist({{ $task->id }}, {{ $index }})">
                                <span>{{ $item['label'] ?? '' }} @if($item['required'] ?? false)<span class="text-danger" aria-label="{{ __('app.required') }}">*</span>@endif</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif

                @if(in_array($task->status, [\App\Enums\TaskStatus::InProgress, \App\Enums\TaskStatus::Reopened], true))
                    <label class="block mt-4 text-sm font-medium">
                        {{ __('app.task_completion_notes') }}
                        <textarea wire:model="completionNotes.{{ $task->id }}" class="form-input mt-1" rows="3" maxlength="1000" placeholder="{{ __('app.optional') }}"></textarea>
                    </label>
                @endif

                <div class="mt-4">
                    @if($task->status === \App\Enums\TaskStatus::Assigned)
                        <button type="button" wire:click="accept({{ $task->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.task_accept') }}</button>
                    @elseif(in_array($task->status, [\App\Enums\TaskStatus::Accepted, \App\Enums\TaskStatus::Reopened], true))
                        <button type="button" wire:click="start({{ $task->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.task_start') }}</button>
                    @elseif(in_array($task->status, [\App\Enums\TaskStatus::ChangesRequested, \App\Enums\TaskStatus::Rejected], true))
                        <button type="button" wire:click="resume({{ $task->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.task_resume') }}</button>
                    @elseif($task->status === \App\Enums\TaskStatus::InProgress)
                        <x-ds.modal :title="__('app.task_submit_title')" :message="__('app.task_submit_message')">
                            <x-slot:trigger>
                                <button type="button" class="btn btn-primary w-full">{{ __('app.task_submit') }}</button>
                            </x-slot:trigger>
                            <x-slot:confirm>
                                <button type="button" wire:click="submit({{ $task->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">
                                    <span wire:loading.remove>{{ __('app.confirm') }}</span>
                                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                                </button>
                            </x-slot:confirm>
                        </x-ds.modal>
                    @elseif($task->status === \App\Enums\TaskStatus::Submitted)
                        <p class="text-sm text-center text-text-muted">{{ __('app.task_waiting_review') }}</p>
                    @endif
                </div>
            </article>
        @empty
            <x-ds.empty icon="heroicon-o-clipboard-document-check" :message="__('app.no_tasks')" />
        @endforelse

        {{ $tasks->links() }}
    </div>

    <x-tab-bar active="more" />
</div>
