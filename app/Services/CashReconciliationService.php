<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\CashBox;
use App\Models\CashReconciliation;
use Illuminate\Support\Facades\DB;

/**
 * End-of-day cash reconciliation: the rep counts physical cash, the system
 * snapshots the expected cash-box balance, and the signed variance is recorded
 * for a manager to approve or flag. This is an audit record — it never mutates
 * the cash box itself (a correction is a separate, explicit action).
 */
class CashReconciliationService
{
    public function submit(int $companyId, int $userId, float $countedAmount, string $notes = '', ?int $workSessionId = null): CashReconciliation
    {
        return DB::transaction(function () use ($companyId, $userId, $countedAmount, $notes, $workSessionId): CashReconciliation {
            $cashBox = CashBox::where('user_id', $userId)->lockForUpdate()->first();
            $expected = $cashBox ? (float) $cashBox->balance : 0.0;
            $variance = round($countedAmount - $expected, 2);

            $reconciliation = CashReconciliation::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'work_session_id' => $workSessionId,
                'expected_amount' => $expected,
                'counted_amount' => $countedAmount,
                'variance' => $variance,
                'notes' => $notes ?: null,
                'status' => 'pending',
            ]);

            Activity::log('cash_reconciliation_submitted', $reconciliation,
                "Cash reconciliation: counted {$countedAmount} vs expected {$expected} (variance {$variance})");

            return $reconciliation;
        });
    }

    public function approve(CashReconciliation $reconciliation, int $reviewerId, string $reviewNotes = ''): CashReconciliation
    {
        return $this->review($reconciliation, $reviewerId, 'approved', $reviewNotes);
    }

    public function flag(CashReconciliation $reconciliation, int $reviewerId, string $reviewNotes = ''): CashReconciliation
    {
        return $this->review($reconciliation, $reviewerId, 'flagged', $reviewNotes);
    }

    private function review(CashReconciliation $reconciliation, int $reviewerId, string $status, string $reviewNotes): CashReconciliation
    {
        return DB::transaction(function () use ($reconciliation, $reviewerId, $status, $reviewNotes): CashReconciliation {
            $fresh = CashReconciliation::whereKey($reconciliation->getKey())->lockForUpdate()->firstOrFail();

            throw_if($fresh->status !== 'pending', new \RuntimeException(
                "This reconciliation has already been reviewed (status: {$fresh->status})."));

            $fresh->update([
                'status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes ?: null,
            ]);

            Activity::log("cash_reconciliation_{$status}", $fresh,
                "Cash reconciliation #{$fresh->id} {$status} by reviewer {$reviewerId}");

            return $fresh;
        });
    }
}
