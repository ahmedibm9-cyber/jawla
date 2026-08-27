<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CallService
{
    /** @param array<string, mixed> $data */
    public function create(int $companyId, int $userId, array $data): Call
    {
        return DB::transaction(function () use ($companyId, $userId, $data): Call {
            return Call::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'customer_id' => $data['customer_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'direction' => $data['direction'] ?? 'outbound',
                'duration_seconds' => $data['duration_seconds'],
                'outcome' => $data['outcome'],
                'notes' => $data['notes'] ?? null,
                'called_at' => $data['called_at'] ?? now(),
            ]);
        });
    }

    /**
     * @return Collection<int, Call>
     */
    public function getForCustomer(int $customerId, int $limit = 20): Collection
    {
        return Call::query()
            ->where('customer_id', $customerId)
            ->where('is_active', true)
            ->with(['user', 'contact'])
            ->orderBy('called_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Call>
     */
    public function getForCompany(int $companyId, array $filters = []): Collection
    {
        $query = Call::query()
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('called_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('called_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('called_at', 'desc')->get();
    }
}
