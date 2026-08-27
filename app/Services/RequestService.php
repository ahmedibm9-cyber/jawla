<?php

namespace App\Services;

use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Collection;

class RequestService
{
    public function create(array $data): Request
    {
        return Request::create([
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'new',
            'is_active' => true,
        ]);
    }

    public function approve(Request $request, User $reviewer, ?string $notes = null): Request
    {
        $request->approve($reviewer->id, $notes);

        return $request->fresh();
    }

    public function reject(Request $request, User $reviewer, string $reason): Request
    {
        $request->reject($reviewer->id, $reason);

        return $request->fresh();
    }

    public function markDone(Request $request): Request
    {
        $request->markDone();

        return $request->fresh();
    }

    public function getForCompany(int $companyId, array $filters = []): Collection
    {
        $query = Request::where('company_id', $companyId)
            ->where('is_active', true);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        return $query->latest()->get();
    }

    public function getPending(int $companyId): Collection
    {
        return $this->getForCompany($companyId, ['status' => 'new']);
    }
}
