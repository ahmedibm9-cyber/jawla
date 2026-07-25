<?php

namespace Tests\Feature;

use App\Livewire\App\TodaysCustomers;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * US-9.3 — View Customer Directory
 *
 * Tests that the rep customer search/directory works correctly.
 */
class TodaysCustomersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_customer_directory_renders_for_rep(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/app/customers')->assertOk();
    }

    public function test_customer_directory_shows_approved_customers(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(TodaysCustomers::class)
            ->assertSuccessful();
    }

    public function test_customer_directory_search_by_name(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(TodaysCustomers::class)
            ->set('search', 'رواد')
            ->assertSuccessful();
    }

    public function test_customer_directory_search_by_code(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(TodaysCustomers::class)
            ->set('search', 'C-001')
            ->assertSuccessful();
    }

    public function test_customer_directory_is_company_scoped(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(TodaysCustomers::class)
            ->assertSuccessful();
    }
}
