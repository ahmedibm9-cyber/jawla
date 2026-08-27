<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\Todos;
use App\Models\Company;
use App\Models\Todo;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    private function rep(Company $company): User
    {
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');

        return $rep;
    }

    public function test_create_validates_required_fields(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);
        app(ActiveCompanyContext::class)->setCompanyId($company->id);

        Livewire::test(Todos::class)
            ->set('newTitle', '')
            ->set('newDueDate', '')
            ->call('createTodo')
            ->assertHasErrors(['newTitle', 'newDueDate']);
    }

    public function test_create_todo(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);
        app(ActiveCompanyContext::class)->setCompanyId($company->id);

        Livewire::test(Todos::class)
            ->set('newTitle', 'Follow up with customer')
            ->set('newDescription', 'Call regarding order status')
            ->set('newPriority', 'high')
            ->set('newDueDate', now()->addDay()->format('Y-m-d'))
            ->call('createTodo')
            ->assertSet('successMessage', fn ($msg) => $msg !== null)
            ->assertSet('showCreateForm', false);

        $this->assertDatabaseHas('todos', [
            'user_id' => $rep->id,
            'title' => 'Follow up with customer',
            'priority' => 'high',
            'status' => 'new',
        ]);
    }

    public function test_complete_todo(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);
        app(ActiveCompanyContext::class)->setCompanyId($company->id);

        $todo = Todo::create([
            'company_id' => $company->id,
            'user_id' => $rep->id,
            'title' => 'Test todo',
            'priority' => 'medium',
            'status' => 'new',
            'due_date' => now(),
        ]);

        Livewire::test(Todos::class)
            ->call('complete', $todo->id)
            ->assertSet('successMessage', fn ($msg) => $msg !== null);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => 'done',
        ]);
    }

    public function test_filter_by_status(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(Todos::class)
            ->set('statusFilter', 'done')
            ->assertSet('statusFilter', 'done');
    }

    public function test_toggle_create_form(): void
    {
        $company = Company::factory()->create();
        $rep = $this->rep($company);
        $this->actingAs($rep);

        Livewire::test(Todos::class)
            ->assertSet('showCreateForm', false)
            ->call('toggleCreateForm')
            ->assertSet('showCreateForm', true)
            ->call('toggleCreateForm')
            ->assertSet('showCreateForm', false);
    }
}
