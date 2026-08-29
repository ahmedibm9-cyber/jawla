<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /** @param array<string, mixed> $data */
    public function create(int $companyId, int $userId, array $data): Ticket
    {
        return DB::transaction(function () use ($companyId, $userId, $data): Ticket {
            $ticket = Ticket::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'customer_id' => $data['customer_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'new',
                'priority' => $data['priority'] ?? 'medium',
            ]);

            $ticket->statusHistory()->create([
                'old_status' => null,
                'new_status' => 'new',
                'changed_by' => $userId,
                'changed_at' => now(),
                'notes' => 'Ticket created',
            ]);

            return $ticket;
        });
    }

    public function transitionTo(Ticket $ticket, string $newStatus, int $userId, ?string $notes = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $newStatus, $userId, $notes): Ticket {
            // Re-fetch with lock to prevent concurrent state transitions
            $ticket = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $ticket->transitionTo($newStatus, $userId, $notes);

            return $ticket->fresh();
        });
    }

    public function assign(Ticket $ticket, int $assigneeId): Ticket
    {
        return DB::transaction(function () use ($ticket, $assigneeId): Ticket {
            $ticket->assign($assigneeId);

            return $ticket->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ticket>
     */
    public function getForCompany(int $companyId, array $filters = []): Collection
    {
        $query = Ticket::query()
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ticket>
     */
    public function getForUser(int $userId, array $filters = []): Collection
    {
        $query = Ticket::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('assigned_to', $userId);
            })
            ->where('is_active', true);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
