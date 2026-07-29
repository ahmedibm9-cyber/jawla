<?php

namespace Tests\Feature\Livewire;

use App\Livewire\App\SyncQueue;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SyncQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_renders_without_error(): void
    {
        $company = Company::factory()->create();
        $rep = User::factory()->create(['company_id' => $company->id]);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        Livewire::test(SyncQueue::class)
            ->assertOk();
    }
}
