<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Todo;
use App\Models\User;
use App\Services\TodoService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($this->company->id);

        $this->rep = User::factory()->for($this->company)->create();
        $this->rep->assignRole('sales_rep');
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_create_todo(): void
    {
        $service = app(TodoService::class);

        $todo = $service->create($this->rep->id, [
            'title' => 'Follow up with customer',
            'description' => 'Call regarding order status',
            'priority' => 'high',
            'due_date' => now()->addDay(),
        ]);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $this->rep->id,
            'title' => 'Follow up with customer',
            'priority' => 'high',
            'status' => 'new',
        ]);
    }

    public function test_complete_todo(): void
    {
        $todo = Todo::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test todo',
            'priority' => 'medium',
            'status' => 'new',
            'due_date' => now(),
        ]);

        $service = app(TodoService::class);
        $completed = $service->complete($todo);

        $this->assertEquals('done', $completed->status);
        $this->assertNotNull($completed->completed_at);
    }

    public function test_get_for_user(): void
    {
        Todo::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test todo',
            'priority' => 'medium',
            'status' => 'new',
            'due_date' => now(),
        ]);

        $service = app(TodoService::class);
        $todos = $service->getForUser($this->rep->id);

        $this->assertCount(1, $todos);
    }

    public function test_get_for_user_with_filters(): void
    {
        Todo::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'New todo',
            'priority' => 'medium',
            'status' => 'new',
            'due_date' => now(),
        ]);

        Todo::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Done todo',
            'priority' => 'low',
            'status' => 'done',
            'due_date' => now()->subDay(),
            'completed_at' => now(),
        ]);

        $service = app(TodoService::class);
        $todos = $service->getForUser($this->rep->id, ['status' => 'new']);

        $this->assertCount(1, $todos);
        $this->assertEquals('new', $todos->first()->status);
    }

    public function test_get_for_company(): void
    {
        Todo::create([
            'company_id' => $this->company->id,
            'user_id' => $this->rep->id,
            'title' => 'Test todo',
            'priority' => 'medium',
            'status' => 'new',
            'due_date' => now(),
        ]);

        $service = app(TodoService::class);
        $todos = $service->getForCompany($this->company->id);

        $this->assertCount(1, $todos);
    }
}
