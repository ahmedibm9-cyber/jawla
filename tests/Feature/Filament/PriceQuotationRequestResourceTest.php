<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PriceQuotationRequestResource\Pages\ListQuotationRequests;
use App\Models\Company;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PriceQuotationRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('admin');
        app(ActiveCompanyContext::class)->setFromUser($admin);
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_list_page_renders(): void
    {
        Livewire::test(ListQuotationRequests::class)->assertOk();
    }
}
