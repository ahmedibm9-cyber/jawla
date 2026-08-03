<?php

namespace App\Services;

use App\Enums\ApprovalRequestStatus;
use App\Enums\ApprovalStepStatus;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    /**
     * @param  list<User>  $approvers
     */
    public function submit(Model $approvable, User $submitter, array $approvers): ApprovalRequest
    {
        return DB::transaction(function () use ($approvable, $submitter, $approvers): ApprovalRequest {
            $companyId = (int) $approvable->getAttribute('company_id');
            $this->assertCompanyAccess($submitter, $companyId);

            $approvers = collect($approvers)
                ->unique(fn (User $approver): int => (int) $approver->getKey())
                ->values();

            throw_if($approvers->isEmpty(), new \DomainException('At least one approver is required.'));

            foreach ($approvers as $approver) {
                $this->assertCompanyAccess($approver, $companyId);
            }

            $hasPendingRequest = ApprovalRequest::query()
                ->whereMorphedTo('approvable', $approvable)
                ->where('status', ApprovalRequestStatus::Pending->value)
                ->lockForUpdate()
                ->exists();

            throw_if($hasPendingRequest, new \DomainException('This record already has a pending approval request.'));

            $request = ApprovalRequest::create([
                'company_id' => $companyId,
                'approvable_type' => $approvable->getMorphClass(),
                'approvable_id' => $approvable->getKey(),
                'submitted_by' => $submitter->getKey(),
                'status' => ApprovalRequestStatus::Pending,
                'current_sequence' => 1,
                'submitted_at' => now(),
            ]);

            foreach ($approvers as $index => $approver) {
                $request->steps()->create([
                    'sequence' => $index + 1,
                    'approver_id' => $approver->getKey(),
                    'status' => $index === 0
                        ? ApprovalStepStatus::Pending
                        : ApprovalStepStatus::Waiting,
                ]);
            }

            return $request->load('steps');
        });
    }

    public function approve(ApprovalRequest $request, User $approver): ApprovalRequest
    {
        return $this->decide($request, $approver, ApprovalStepStatus::Approved);
    }

    public function reject(ApprovalRequest $request, User $approver, string $reason): ApprovalRequest
    {
        return $this->decide($request, $approver, ApprovalStepStatus::Rejected, $reason);
    }

    public function requestChanges(ApprovalRequest $request, User $approver, string $reason): ApprovalRequest
    {
        return $this->decide($request, $approver, ApprovalStepStatus::ChangesRequested, $reason);
    }

    private function decide(
        ApprovalRequest $request,
        User $approver,
        ApprovalStepStatus $decision,
        ?string $reason = null,
    ): ApprovalRequest {
        return DB::transaction(function () use ($request, $approver, $decision, $reason): ApprovalRequest {
            $request = ApprovalRequest::query()->lockForUpdate()->findOrFail($request->getKey());
            $this->assertCompanyAccess($approver, (int) $request->company_id);

            throw_if(
                $request->status !== ApprovalRequestStatus::Pending,
                new \DomainException('This approval request is no longer pending.'),
            );

            $step = ApprovalStep::query()
                ->where('approval_request_id', $request->getKey())
                ->where('sequence', $request->current_sequence)
                ->lockForUpdate()
                ->firstOrFail();

            throw_unless((int) $step->approver_id === (int) $approver->getKey(), new AuthorizationException(
                'Only the current approver may decide this request.',
            ));
            throw_if($step->status !== ApprovalStepStatus::Pending, new \DomainException(
                'This approval step is no longer pending.',
            ));

            $reason = trim((string) $reason);
            throw_if(
                $decision !== ApprovalStepStatus::Approved && $reason === '',
                new \DomainException('A decision reason is required.'),
            );

            $step->update([
                'status' => $decision,
                'decision_reason' => $reason ?: null,
                'decided_at' => now(),
            ]);

            if ($decision !== ApprovalStepStatus::Approved) {
                $request->update([
                    'status' => $decision === ApprovalStepStatus::Rejected
                        ? ApprovalRequestStatus::Rejected
                        : ApprovalRequestStatus::ChangesRequested,
                    'completed_at' => now(),
                ]);

                return $request->fresh('steps');
            }

            $nextStep = $request->steps()
                ->where('sequence', '>', $request->current_sequence)
                ->orderBy('sequence')
                ->first();

            if ($nextStep === null) {
                $request->update([
                    'status' => ApprovalRequestStatus::Approved,
                    'completed_at' => now(),
                ]);
            } else {
                $nextStep->update(['status' => ApprovalStepStatus::Pending]);
                $request->update(['current_sequence' => $nextStep->sequence]);
            }

            return $request->fresh('steps');
        });
    }

    private function assertCompanyAccess(User $user, int $companyId): void
    {
        throw_unless($user->hasCompanyAccess($companyId), new AuthorizationException(
            'Cross-company approval access is forbidden.',
        ));
    }
}
