<?php

namespace App\Services;

use App\Enums\ApprovalRequestStatus;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\ApprovalRequest;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOutcome;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly ApprovalService $approvals,
    ) {}

    public function accept(Task $task, User $rep): Task
    {
        return $this->repTransition($task, $rep, TaskStatus::Assigned, [
            'status' => TaskStatus::Accepted,
            'accepted_at' => now(),
        ], 'task_accepted');
    }

    public function start(Task $task, User $rep): Task
    {
        return $this->repTransition($task, $rep, [TaskStatus::Accepted, TaskStatus::Reopened], [
            'status' => TaskStatus::InProgress,
            'started_at' => now(),
            'decision_reason' => null,
        ], 'task_started');
    }

    public function resume(Task $task, User $rep): Task
    {
        return $this->repTransition($task, $rep, [TaskStatus::ChangesRequested, TaskStatus::Rejected], [
            'status' => TaskStatus::InProgress,
            'started_at' => now(),
            'decision_reason' => null,
        ], 'task_resumed');
    }

    public function setChecklistItem(Task $task, User $rep, int $index, bool $completed): Task
    {
        return DB::transaction(function () use ($task, $rep, $index, $completed): Task {
            $task = $this->lockTask($task);
            $this->assertAssignedRep($task, $rep, 'tasks.progress');
            $this->assertStatus($task, [TaskStatus::Accepted, TaskStatus::InProgress, TaskStatus::Reopened]);

            $checklist = $task->checklist ?? [];
            throw_unless(array_key_exists($index, $checklist), new \OutOfBoundsException(
                'The requested checklist item does not exist.',
            ));

            $checklist[$index]['completed'] = $completed;
            $checklist[$index]['completed_at'] = $completed ? now()->toIso8601String() : null;
            $checklist[$index]['completed_by'] = $completed ? $rep->getKey() : null;
            $task->update(['checklist' => array_values($checklist)]);

            return $task->fresh();
        });
    }

    public function submit(Task $task, User $rep, ?string $completionNotes = null): ?ApprovalRequest
    {
        return DB::transaction(function () use ($task, $rep, $completionNotes): ?ApprovalRequest {
            $task = $this->lockTask($task);
            $this->assertAssignedRep($task, $rep, 'tasks.submit');
            $this->assertStatus($task, [TaskStatus::InProgress, TaskStatus::Reopened]);
            $this->assertChecklistComplete($task);

            if (! $task->requires_approval) {
                $task->update([
                    'status' => TaskStatus::Approved,
                    'completion_notes' => $completionNotes,
                    'submitted_at' => now(),
                    'completed_at' => now(),
                    'approved_at' => now(),
                ]);
                Activity::log('task_completed', $task, "Task #{$task->id} completed without review");

                return null;
            }

            $approvers = collect([$task->reviewer, $task->finalApprover])
                ->filter()
                ->values()
                ->all();

            $request = $this->approvals->submit($task, $rep, $approvers);
            $task->update([
                'status' => TaskStatus::Submitted,
                'completion_notes' => $completionNotes,
                'submitted_at' => now(),
                'decision_reason' => null,
            ]);
            Activity::log('task_submitted', $task, "Task #{$task->id} submitted for approval");

            return $request;
        });
    }

    public function approve(ApprovalRequest $request, User $approver): Task
    {
        return DB::transaction(function () use ($request, $approver): Task {
            $this->assertTaskApproval($request);
            throw_unless($approver->can('tasks.approve'), new AuthorizationException(
                'You do not have permission to approve tasks.',
            ));

            $request = $this->approvals->approve($request, $approver);
            /** @var Task $task */
            $task = $request->approvable;

            if ($request->status === ApprovalRequestStatus::Approved) {
                $task->update([
                    'status' => TaskStatus::Approved,
                    'approved_at' => now(),
                    'completed_at' => now(),
                    'decision_reason' => null,
                ]);
                $this->notifyRepAfterCommit($task, 'approved');
                DB::afterCommit(fn () => app(WebhookService::class)->dispatch((int) $task->company_id, 'task.approved', [
                    'task_id' => $task->id,
                    'assigned_to' => $task->assigned_to,
                    'approved_at' => $task->approved_at?->toIso8601String(),
                ]));
                Activity::log('task_approved', $task, "Task #{$task->id} approved");
            } else {
                Activity::log('task_approval_advanced', $task, "Task #{$task->id} advanced to the next approver");
            }

            return $task->fresh();
        });
    }

    public function reject(ApprovalRequest $request, User $approver, string $reason): Task
    {
        return $this->negativeDecision($request, $approver, $reason, false);
    }

    public function requestChanges(ApprovalRequest $request, User $approver, string $reason): Task
    {
        return $this->negativeDecision($request, $approver, $reason, true);
    }

    public function reopen(Task $task, User $manager, string $reason): Task
    {
        return DB::transaction(function () use ($task, $manager, $reason): Task {
            $task = $this->lockTask($task);
            $this->assertCompanyAccess($task, $manager);
            throw_unless($manager->can('tasks.reopen'), new AuthorizationException(
                'You do not have permission to reopen tasks.',
            ));
            $this->assertStatus($task, TaskStatus::Approved);
            $reason = $this->requiredReason($reason);

            $task->update([
                'status' => TaskStatus::Reopened,
                'decision_reason' => $reason,
                'reopened_at' => now(),
                'completed_at' => null,
                'approved_at' => null,
            ]);
            Activity::log('task_reopened', $task, "Task #{$task->id} reopened", ['reason' => $reason]);
            $this->notifyRepAfterCommit($task, 'reopened', $reason);

            return $task->fresh();
        });
    }

    private function negativeDecision(
        ApprovalRequest $request,
        User $approver,
        string $reason,
        bool $changesRequested,
    ): Task {
        return DB::transaction(function () use ($request, $approver, $reason, $changesRequested): Task {
            $this->assertTaskApproval($request);
            throw_unless($approver->can('tasks.reject'), new AuthorizationException(
                'You do not have permission to return or reject tasks.',
            ));
            $reason = $this->requiredReason($reason);

            $request = $changesRequested
                ? $this->approvals->requestChanges($request, $approver, $reason)
                : $this->approvals->reject($request, $approver, $reason);

            /** @var Task $task */
            $task = $request->approvable;
            $task->update([
                'status' => $changesRequested ? TaskStatus::ChangesRequested : TaskStatus::Rejected,
                'decision_reason' => $reason,
                'rejected_at' => now(),
            ]);

            $event = $changesRequested ? 'task_changes_requested' : 'task_rejected';
            Activity::log($event, $task, "Task #{$task->id} requires rep attention", ['reason' => $reason]);
            $this->notifyRepAfterCommit($task, $changesRequested ? 'changes_requested' : 'rejected', $reason);

            return $task->fresh();
        });
    }

    /**
     * @param  TaskStatus|list<TaskStatus>  $expected
     * @param  array<string, mixed>  $changes
     */
    private function repTransition(
        Task $task,
        User $rep,
        TaskStatus|array $expected,
        array $changes,
        string $activity,
    ): Task {
        return DB::transaction(function () use ($task, $rep, $expected, $changes, $activity): Task {
            $task = $this->lockTask($task);
            $permission = $expected === TaskStatus::Assigned ? 'tasks.accept' : 'tasks.progress';
            $this->assertAssignedRep($task, $rep, $permission);
            $this->assertStatus($task, $expected);
            $task->update($changes);
            Activity::log($activity, $task, "Task #{$task->id} status changed to {$task->status->value}");

            return $task->fresh();
        });
    }

    private function lockTask(Task $task): Task
    {
        return Task::query()->lockForUpdate()->findOrFail($task->getKey());
    }

    private function assertAssignedRep(Task $task, User $rep, string $permission): void
    {
        $this->assertCompanyAccess($task, $rep);
        throw_unless((int) $task->assigned_to === (int) $rep->getKey(), new AuthorizationException(
            'Only the assigned representative may perform this task action.',
        ));
        throw_unless($rep->can($permission), new AuthorizationException(
            'You do not have permission to perform this task action.',
        ));
    }

    private function assertCompanyAccess(Task $task, User $user): void
    {
        throw_unless($user->hasCompanyAccess((int) $task->company_id), new AuthorizationException(
            'Cross-company task access is forbidden.',
        ));
    }

    /** @param TaskStatus|list<TaskStatus> $expected */
    private function assertStatus(Task $task, TaskStatus|array $expected): void
    {
        $expected = is_array($expected) ? $expected : [$expected];
        throw_unless(in_array($task->status, $expected, true), new \DomainException(
            "Task cannot transition from {$task->status->value}.",
        ));
    }

    private function assertChecklistComplete(Task $task): void
    {
        $incomplete = collect($task->checklist ?? [])->contains(
            fn (array $item): bool => ($item['required'] ?? false) && ! ($item['completed'] ?? false),
        );

        throw_if($incomplete, new \DomainException('All required checklist items must be completed.'));
    }

    private function assertTaskApproval(ApprovalRequest $request): void
    {
        throw_unless($request->approvable_type === (new Task)->getMorphClass(), new \InvalidArgumentException(
            'This approval request does not belong to a task.',
        ));
    }

    private function requiredReason(string $reason): string
    {
        $reason = trim($reason);
        throw_if($reason === '', new \DomainException('A reason is required.'));

        return $reason;
    }

    private function notifyRepAfterCommit(Task $task, string $outcome, ?string $reason = null): void
    {
        DB::afterCommit(function () use ($task, $outcome, $reason): void {
            $task->assignedTo?->notify(new TaskOutcome($task, $outcome, $reason));
        });
    }
}
