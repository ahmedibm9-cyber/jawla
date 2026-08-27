<?php

namespace App\Services;

use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TodoService
{
    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): Todo
    {
        return DB::transaction(function () use ($userId, $data): Todo {
            return Todo::create([
                'user_id' => $userId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'new',
                'due_date' => $data['due_date'],
            ]);
        });
    }

    public function complete(Todo $todo): Todo
    {
        return DB::transaction(function () use ($todo): Todo {
            $todo->complete();

            return $todo->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Todo>
     */
    public function getForUser(int $userId, array $filters = []): Collection
    {
        $query = Todo::query()
            ->where('user_id', $userId)
            ->where('is_active', true);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['due_date'])) {
            $query->where('due_date', $filters['due_date']);
        }

        return $query->orderBy('due_date')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Todo>
     */
    public function getForCompany(int $companyId, array $filters = []): Collection
    {
        $query = Todo::query()
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('due_date')->get();
    }
}
