<?php

namespace App\Livewire\App;

use App\Models\Todo;
use App\Services\TodoService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Todos extends Component
{
    use WithPagination;

    public string $statusFilter = 'new';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $showCreateForm = false;

    public string $newTitle = '';

    public ?string $newDescription = null;

    public string $newPriority = 'medium';

    public ?string $newDueDate = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->reset(['newTitle', 'newDescription', 'newPriority', 'newDueDate']);
    }

    public function createTodo(): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        $this->validate([
            'newTitle' => 'required|string|max:255',
            'newDescription' => 'nullable|string',
            'newPriority' => 'required|in:low,medium,high',
            'newDueDate' => 'required|date',
        ]);

        try {
            app(TodoService::class)->create(auth()->id(), [
                'title' => $this->newTitle,
                'description' => $this->newDescription,
                'priority' => $this->newPriority,
                'due_date' => $this->newDueDate,
            ]);

            $this->successMessage = __('app.todo_created');
            $this->showCreateForm = false;
            $this->reset(['newTitle', 'newDescription', 'newPriority', 'newDueDate']);
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.todo_create_failed');
        }
    }

    public function complete(int $todoId): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        try {
            $todo = Todo::where('user_id', auth()->id())->findOrFail($todoId);
            app(TodoService::class)->complete($todo);
            $this->successMessage = __('app.todo_completed');
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.todo_action_failed');
        }
    }

    public function render(): View
    {
        $query = Todo::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true);

        if ($this->statusFilter === 'done') {
            $query->where('status', 'done');
        } else {
            $query->where('status', 'new');
        }

        return view('livewire.app.todos', [
            'todos' => $query->orderBy('due_date')->paginate(20),
        ]);
    }
}
