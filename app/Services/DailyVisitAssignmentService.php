<?php

namespace App\Services;

use App\Enums\ApprovalRequestStatus;
use App\Models\ApprovalRequest;
use App\Models\DailyVisitAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DailyVisitAssignmentService
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly WorkflowApproverResolver $approverResolver,
    ) {}

    public function submit(DailyVisitAssignment $assignment, User $submitter): DailyVisitAssignment
    {
        throw_unless($submitter->can('daily_visit_assignments.create'), new AuthorizationException(
            'You cannot submit visit plans.',
        ));
        throw_unless($assignment->status === 'draft', new \DomainException(
            'Only draft assignments can be submitted.',
        ));

        return DB::transaction(function () use ($assignment, $submitter): DailyVisitAssignment {
            $approver = $this->approverResolver->forSubmitter($submitter, 'visit_plans.approve');
            $this->approvals->submit($assignment, $submitter, [$approver]);
            $assignment->update(['status' => 'pending_approval', 'submitted_at' => now()]);

            return $assignment->fresh(['latestApproval.steps']);
        });
    }

    public function approve(ApprovalRequest $request, User $actor): DailyVisitAssignment
    {
        throw_unless($actor->can('visit_plans.approve'), new AuthorizationException(
            'You cannot approve visit plans.',
        ));

        return DB::transaction(function () use ($request, $actor): DailyVisitAssignment {
            $request = ApprovalRequest::query()->with('approvable')->lockForUpdate()->findOrFail($request->id);
            $subject = $request->approvable;
            throw_unless($subject instanceof DailyVisitAssignment, new \InvalidArgumentException('Not a visit plan approval.'));

            $request = $this->approvals->approve($request, $actor);

            if ($request->status === ApprovalRequestStatus::Approved) {
                $subject->update([
                    'status' => 'approved',
                    'approved_by' => $actor->id,
                    'approved_at' => now(),
                ]);
            }

            return $subject->fresh();
        });
    }

    public function reject(ApprovalRequest $request, User $actor, string $reason): DailyVisitAssignment
    {
        throw_unless($actor->can('visit_plans.approve'), new AuthorizationException(
            'You cannot reject visit plans.',
        ));
        $reason = trim($reason);
        throw_if($reason === '', new \DomainException('A rejection reason is required.'));

        return DB::transaction(function () use ($request, $actor, $reason): DailyVisitAssignment {
            $request = ApprovalRequest::query()->with('approvable')->lockForUpdate()->findOrFail($request->id);
            $subject = $request->approvable;
            throw_unless($subject instanceof DailyVisitAssignment, new \InvalidArgumentException('Not a visit plan approval.'));

            $this->approvals->reject($request, $actor, $reason);
            $subject->update(['status' => 'rejected']);

            return $subject->fresh();
        });
    }

    /** @param  array<int>  $customerIds */
    public function bulkAssign(User $submitter, int $userId, string $visitDate, array $customerIds): array
    {
        throw_unless($submitter->can('daily_visit_assignments.create'), new AuthorizationException(
            'You cannot create visit assignments.',
        ));

        $companyId = $submitter->activeCompanyId();
        throw_unless(
            User::where('id', $userId)->where('company_id', $companyId)->exists(),
            new AuthorizationException('Rep does not belong to your company.')
        );

        return DB::transaction(function () use ($submitter, $userId, $visitDate, $customerIds, $companyId): array {
            $created = 0;
            $skipped = 0;

            foreach ($customerIds as $customerId) {
                $exists = DailyVisitAssignment::where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->where('customer_id', $customerId)
                    ->where('visit_date', $visitDate)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                DailyVisitAssignment::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'customer_id' => $customerId,
                    'visit_date' => $visitDate,
                    'status' => 'draft',
                    'assigned_by' => $submitter->id,
                ]);
                $created++;
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /**
     * Mark an approved assignment as completed (typically called by VisitReportService).
     *
     * @throws \DomainException
     */
    public function complete(DailyVisitAssignment $assignment): void
    {
        throw_unless($assignment->status === 'approved', new \DomainException(
            'Only approved assignments can be completed.',
        ));
        $assignment->update(['status' => 'completed']);
    }
}
