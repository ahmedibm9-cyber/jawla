<?php

namespace Tests\Feature;

use App\Enums\ApprovalRequestStatus;
use App\Enums\ApprovalStepStatus;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    private User $manager;

    private User $finalApprover;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        $this->rep = User::factory()->for($this->company)->create()->assignRole('rep');
        $this->manager = User::factory()->for($this->company)->create()->assignRole('sales_manager');
        $this->finalApprover = User::factory()->for($this->company)->create()->assignRole('admin');

        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_assigned_rep_completes_checklist_and_two_approvers_review_in_sequence(): void
    {
        $task = $this->task([
            'checklist' => [
                ['label' => 'Photograph the display', 'required' => true],
                ['label' => 'Record a note', 'required' => false],
            ],
        ]);
        $service = app(TaskService::class);

        $service->accept($task, $this->rep);
        $service->start($task, $this->rep);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('All required checklist items must be completed.');
        $service->submit($task, $this->rep, 'Display corrected');
    }

    public function test_completed_checklist_creates_ordered_approval_and_closes_only_after_final_approval(): void
    {
        $task = $this->task([
            'checklist' => [['label' => 'Photograph the display', 'required' => true]],
        ]);
        $service = app(TaskService::class);

        $service->accept($task, $this->rep);
        $service->start($task, $this->rep);
        $service->setChecklistItem($task, $this->rep, 0, true);
        $request = $service->submit($task, $this->rep, 'Display corrected');

        self::assertNotNull($request);
        self::assertSame(ApprovalRequestStatus::Pending, $request->status);
        self::assertSame(ApprovalStepStatus::Pending, $request->steps[0]->status);
        self::assertSame(ApprovalStepStatus::Waiting, $request->steps[1]->status);
        self::assertSame(TaskStatus::Submitted, $task->fresh()->status);

        $service->approve($request, $this->manager);
        $request->refresh()->load('steps');

        self::assertSame(ApprovalRequestStatus::Pending, $request->status);
        self::assertSame(2, $request->current_sequence);
        self::assertSame(ApprovalStepStatus::Pending, $request->steps[1]->status);
        self::assertSame(TaskStatus::Submitted, $task->fresh()->status);

        $service->approve($request, $this->finalApprover);

        $task->refresh();
        self::assertSame(TaskStatus::Approved, $task->status);
        self::assertNotNull($task->approved_at);
        self::assertNotNull($task->completed_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->rep->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_manager_can_request_changes_and_rep_can_resubmit_with_preserved_history(): void
    {
        $task = $this->task(['final_approver_id' => null]);
        $service = app(TaskService::class);

        $service->accept($task, $this->rep);
        $service->start($task, $this->rep);
        $firstRequest = $service->submit($task, $this->rep);
        $service->requestChanges($firstRequest, $this->manager, 'Attach the shelf photo.');

        $task->refresh();
        self::assertSame(TaskStatus::ChangesRequested, $task->status);
        self::assertSame('Attach the shelf photo.', $task->decision_reason);
        self::assertSame(ApprovalRequestStatus::ChangesRequested, $firstRequest->fresh()->status);

        $service->resume($task, $this->rep);
        $secondRequest = $service->submit($task, $this->rep, 'Photo attached');
        $task->refresh();

        self::assertNotSame($firstRequest->id, $secondRequest?->id);
        self::assertCount(2, $task->approvals()->get());
        self::assertSame(TaskStatus::Submitted, $task->status);
        self::assertNull($task->decision_reason);
    }

    public function test_unassigned_rep_cannot_accept_a_task(): void
    {
        $otherRep = User::factory()->for($this->company)->create()->assignRole('rep');

        $this->expectException(AuthorizationException::class);
        app(TaskService::class)->accept($this->task(), $otherRep);
    }

    public function test_cross_company_approver_cannot_be_added_to_the_workflow(): void
    {
        $otherCompany = Company::factory()->create();
        $externalApprover = User::factory()->for($otherCompany)->create()->assignRole('admin');
        $task = $this->task(['reviewer_id' => $externalApprover->id, 'final_approver_id' => null]);
        $service = app(TaskService::class);

        $service->accept($task, $this->rep);
        $service->start($task, $this->rep);

        $this->expectException(AuthorizationException::class);
        $service->submit($task, $this->rep);
    }

    public function test_task_without_approval_closes_immediately_after_submission(): void
    {
        $task = $this->task([
            'requires_approval' => false,
            'reviewer_id' => null,
            'final_approver_id' => null,
        ]);
        $service = app(TaskService::class);

        $service->accept($task, $this->rep);
        $service->start($task, $this->rep);
        $request = $service->submit($task, $this->rep, 'Done');

        self::assertNull($request);
        self::assertSame(TaskStatus::Approved, $task->fresh()->status);
        self::assertCount(0, $task->approvals()->get());
    }

    /** @param array<string, mixed> $attributes */
    private function task(array $attributes = []): Task
    {
        return Task::create(array_merge([
            'company_id' => $this->company->id,
            'created_by' => $this->manager->id,
            'assigned_to' => $this->rep->id,
            'reviewer_id' => $this->manager->id,
            'final_approver_id' => $this->finalApprover->id,
            'title' => 'Audit customer display',
            'status' => TaskStatus::Assigned,
            'requires_approval' => true,
            'checklist' => [],
        ], $attributes));
    }
}
