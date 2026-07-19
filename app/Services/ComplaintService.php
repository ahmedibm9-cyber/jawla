<?php

namespace App\Services;

use App\Models\Complaint;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    public function __construct(
        private readonly AlarmService $alarms,
    ) {}

    public function log(int $companyId, int $userId, int $customerId, string $type, string $description, ?int $visitId = null): Complaint
    {
        return DB::transaction(function () use ($companyId, $userId, $customerId, $type, $description, $visitId): Complaint {
            $complaint = Complaint::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'visit_id' => $visitId,
                'complaint_type' => $type,
                'description' => $description,
                'status' => 'open',
            ]);

            $this->alarms->raise(
                'customer_complaint',
                $complaint,
                "Complaint: {$complaint->customer?->name_ar}",
                $description,
                'critical',
            );

            return $complaint;
        });
    }

    public function resolve(Complaint $complaint, int $userId, string $resolution): Complaint
    {
        return DB::transaction(function () use ($complaint, $userId, $resolution): Complaint {
            $complaint->update([
                'status' => 'resolved',
                'assigned_to' => $userId,
                'resolution' => $resolution,
                'resolved_at' => now(),
            ]);

            // afterCommit: a rolled-back resolution must never notify the rep.
            DB::afterCommit(function () use ($complaint, $resolution): void {
                $complaint->user?->notify(new \App\Notifications\ComplaintResolved($complaint, $resolution));
            });

            return $complaint;
        });
    }
}
