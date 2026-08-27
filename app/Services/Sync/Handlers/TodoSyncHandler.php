<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\TodoService;

class TodoSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly TodoService $todos) {}

    public function type(): string
    {
        return 'todo';
    }

    public function handle(User $rep, array $payload, ?string $idempotencyKey = null): array
    {
        $data = $this->validated($payload, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
        ]);

        $todo = $this->todos->create(
            userId: $rep->id,
            data: $data,
        );

        return ['todo_id' => $todo->id];
    }
}
