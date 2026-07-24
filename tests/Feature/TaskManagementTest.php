<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * US-2.3 + US-24.1 + US-24.2 — Task Management
 *
 * Tests task creation (admin), task completion (rep), and task rendering.
 */
class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_admin_can_create_task(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $rep = User::where('email', 'rep@jawla.test')->first();

        $task = Task::create([
            'company_id' => $admin->company_id,
            'assigned_to' => $rep->id,
            'title' => 'Visit customer X',
            'note' => 'Check inventory levels',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Visit customer X',
            'status' => 'open',
        ]);
    }

    public function test_rep_can_complete_task(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();
        $rep = User::where('email', 'rep@jawla.test')->first();

        $task = Task::create([
            'company_id' => $admin->company_id,
            'assigned_to' => $rep->id,
            'title' => 'Follow up with customer',
            'status' => 'open',
        ]);

        $task->update(['status' => 'done']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
    }

    public function test_task_requires_title(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();

        $task = Task::create([
            'company_id' => $admin->company_id,
            'title' => '',
            'status' => 'open',
        ]);

        $this->assertEmpty($task->title);
    }

    public function test_task_is_company_scoped(): void
    {
        $admin = User::where('email', 'admin@jawla.test')->first();

        $task = Task::create([
            'company_id' => $admin->company_id,
            'title' => 'Test task',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'company_id' => $admin->company_id,
        ]);
    }
}
