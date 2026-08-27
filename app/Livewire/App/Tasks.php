<?php

namespace App\Livewire\App;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Tasks extends Component
{
    use WithPagination;

    public string $statusFilter = 'active';

    /** @var array<int, string> */
    public array $completionNotes = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function accept(int $taskId): void
    {
        $this->runTaskAction($taskId, fn (Task $task) => app(TaskService::class)->accept($task, auth()->user()));
    }

    public function start(int $taskId): void
    {
        $this->runTaskAction($taskId, fn (Task $task) => app(TaskService::class)->start($task, auth()->user()));
    }

    public function resume(int $taskId): void
    {
        $this->runTaskAction($taskId, fn (Task $task) => app(TaskService::class)->resume($task, auth()->user()));
    }

    public function toggleChecklist(int $taskId, int $index): void
    {
        $this->runTaskAction($taskId, function (Task $task) use ($index): void {
            $completed = (bool) data_get($task->checklist, "{$index}.completed", false);
            app(TaskService::class)->setChecklistItem($task, auth()->user(), $index, ! $completed);
        }, __('app.task_checklist_updated'));
    }

    public function submit(int $taskId): void
    {
        $this->runTaskAction($taskId, function (Task $task) use ($taskId): void {
            app(TaskService::class)->submit($task, auth()->user(), $this->completionNotes[$taskId] ?? null);
            unset($this->completionNotes[$taskId]);
        }, __('app.task_submitted'));
    }

    public function render(): View
    {
        $query = Task::query()
            ->where('assigned_to', auth()->id())
            ->with(['customer', 'latestApproval.steps'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest('id');

        if ($this->statusFilter === 'completed') {
            $query->whereIn('status', [TaskStatus::Approved->value, TaskStatus::Cancelled->value]);
        } else {
            $query->whereNotIn('status', [TaskStatus::Approved->value, TaskStatus::Cancelled->value]);
        }

        return view('livewire.app.tasks', [
            'tasks' => $query->paginate(20),
        ]);
    }

    private function ownedTask(int $taskId): Task
    {
        return Task::query()
            ->where('assigned_to', auth()->id())
            ->findOrFail($taskId);
    }

    private function runTaskAction(int $taskId, callable $action, ?string $success = null): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        try {
            $action($this->ownedTask($taskId));
            $this->successMessage = $success ?? __('app.task_updated');
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception instanceof \DomainException
                ? $exception->getMessage()
                : __('app.task_action_failed');
        }
    }
}
