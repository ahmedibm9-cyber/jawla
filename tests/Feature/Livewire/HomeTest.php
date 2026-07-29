<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\Home;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyVisitAssignment;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_mount_counts_today_assignments(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        DailyVisitAssignment::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
            'visit_date' => today(),
            'status' => 'pending',
            'assigned_by' => $rep->id,
        ]);

        Livewire::test(Home::class)
            ->assertSet('visitCount', 1);
    }

    public function test_start_work_creates_session(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(Home::class)
            ->set('startLat', 24.7136)
            ->set('startLng', 46.6753)
            ->call('startWork');

        $this->assertDatabaseHas('work_sessions', [
            'user_id' => $rep->id,
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ]);
    }

    public function test_start_work_reuses_existing_session(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        WorkSession::create([
            'user_id' => $rep->id,
            'started_at' => now(),
            'start_latitude' => 24.7136,
            'start_longitude' => 46.6753,
        ]);
        session(['work_session_id' => WorkSession::where('user_id', $rep->id)->first()->id]);
        $this->actingAs($rep);

        Livewire::test(Home::class)->call('startWork');

        $this->assertEquals(1, WorkSession::where('user_id', $rep->id)->count());
    }

    public function test_complete_task_marks_done(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        $task = Task::create([
            'company_id' => $company->id,
            'created_by' => $rep->id,
            'assigned_to' => $rep->id,
            'title' => 'Follow up',
            'status' => 'open',
        ]);
        $this->actingAs($rep);

        Livewire::test(Home::class)
            ->call('completeTask', $task->id)
            ->assertSet('successMessage', fn ($msg) => str_contains($msg, 'completed') || str_contains($msg, 'تم إكمال'));

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }
}
