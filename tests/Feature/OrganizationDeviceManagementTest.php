<?php

namespace Tests\Feature;

use App\Enums\DeviceStatus;
use App\Models\Company;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\DeviceService;
use App\Services\OrganizationScopeService;
use App\Support\ActiveCompanyContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationDeviceManagementTest extends TestCase
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

    public function test_manager_scope_includes_users_in_descendant_units_only(): void
    {
        $company = Company::factory()->create();
        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $manager = User::factory()->for($company)->create()->assignRole('sales_manager');
        $scopedRep = User::factory()->for($company)->create()->assignRole('rep');
        $otherRep = User::factory()->for($company)->create()->assignRole('rep');

        $region = $this->unit($company, 'region', 'REG-1');
        $branch = $this->unit($company, 'branch', 'BR-1', $region);
        $area = $this->unit($company, 'area', 'AR-1', $branch);
        $team = $this->unit($company, 'team', 'TM-1', $area);
        $otherBranch = $this->unit($company, 'branch', 'BR-2', $region);

        $manager->organizationUnits()->attach($branch);
        $scopedRep->update(['primary_organization_unit_id' => $team->id]);
        $otherRep->update(['primary_organization_unit_id' => $otherBranch->id]);

        $visibleIds = app(OrganizationScopeService::class)
            ->scopeUsers(User::query(), $manager)
            ->pluck('id');

        self::assertTrue($visibleIds->contains($scopedRep->id));
        self::assertFalse($visibleIds->contains($otherRep->id));
    }

    public function test_required_device_stays_blocked_until_approved_and_is_blocked_again_after_revocation(): void
    {
        $company = Company::factory()->create(['require_approved_devices' => true]);
        $rep = User::factory()->for($company)->create()->assignRole('rep');
        $admin = User::factory()->for($company)->create()->assignRole('admin');
        $deviceUuid = (string) Str::uuid();

        $this->actingAs($rep)->get('/app')->assertRedirect(route('app.device'));

        app(ActiveCompanyContext::class)->setCompanyId($company->id);
        $device = app(DeviceService::class)->register($rep, $deviceUuid, 'Rep phone', 'Android');
        self::assertSame(DeviceStatus::Pending, $device->status);

        app(DeviceService::class)->approve($device, $admin);
        $this->actingAs($rep)->withCookie('jawla_device_id', $deviceUuid)->get('/app')->assertOk();

        app(DeviceService::class)->revoke($device, $admin);
        $this->actingAs($rep)->withCookie('jawla_device_id', $deviceUuid)->get('/app')
            ->assertRedirect(route('app.device'));
    }

    private function unit(
        Company $company,
        string $type,
        string $code,
        ?OrganizationUnit $parent = null,
    ): OrganizationUnit {
        return OrganizationUnit::create([
            'company_id' => $company->id,
            'parent_id' => $parent?->id,
            'type' => $type,
            'code' => $code,
            'name_ar' => $code,
            'name_en' => $code,
        ]);
    }
}
