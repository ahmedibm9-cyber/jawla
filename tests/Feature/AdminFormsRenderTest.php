<?php

namespace Tests\Feature;

use App\Filament\Resources\BatchResource\Pages\CreateBatch;
use App\Filament\Resources\ComplaintResource\Pages\CreateComplaint;
use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\DailyVisitAssignmentResource\Pages\CreateDailyVisitAssignment;
use App\Filament\Resources\GoodsInTransitResource\Pages\CreateGoodsInTransit;
use App\Filament\Resources\PriceQuotationRequestResource\Pages\CreateQuotationRequest;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Company;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression guard for the Filament v4 `Section` import: these create forms all
 * declare a Section, which lives at Filament\Schemas\Components\Section — NOT the
 * (nonexistent) Filament\Forms\Components\Section. Rendering each form proves the
 * class resolves; before the fix every one of these fataled on mount.
 */
class AdminFormsRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->assignRole('admin');
        app(ActiveCompanyContext::class)->setFromUser($this->admin);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_create_forms_render_without_fatal(): void
    {
        $pages = [
            CreateProduct::class,
            CreateCustomer::class,
            CreateUser::class,
            CreateTask::class,
            CreateComplaint::class,
            CreateQuotationRequest::class,
            CreateDailyVisitAssignment::class,
            CreateBatch::class,
            CreateGoodsInTransit::class,
        ];

        foreach ($pages as $page) {
            Livewire::actingAs($this->admin)
                ->test($page)
                ->assertOk();
        }

        $this->assertTrue(true);
    }
}
