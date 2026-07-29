<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Support\ApiAbilities;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_issue_creates_token_with_abilities(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;
        $result = $service->issue($user, 'test-token', [ApiAbilities::READ_PRODUCTS]);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('plainTextToken', $result);
        $this->assertEquals('test-token', $result['token']->name);
        $this->assertContains(ApiAbilities::READ_PRODUCTS, $result['token']->abilities);
    }

    public function test_issue_logs_activity(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;
        $service->issue($user, 'test-token', [ApiAbilities::READ_PRODUCTS]);

        $this->assertDatabaseHas('activities', [
            'type' => 'api_token_issued',
        ]);
    }

    public function test_issue_rejects_empty_name(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Token name is required');
        $service->issue($user, '', [ApiAbilities::READ_PRODUCTS]);
    }

    public function test_issue_rejects_unknown_ability(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown ability');
        $service->issue($user, 'test-token', ['unknown:ability']);
    }

    public function test_issue_rejects_empty_abilities(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one ability is required');
        $service->issue($user, 'test-token', []);
    }

    public function test_revoke_deletes_token(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;
        $result = $service->issue($user, 'test-token', [ApiAbilities::READ_PRODUCTS]);

        $service->revoke($result['token']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $result['token']->id,
        ]);
    }

    public function test_revoke_logs_activity(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $service = new ApiTokenService;
        $result = $service->issue($user, 'test-token', [ApiAbilities::READ_PRODUCTS]);

        $service->revoke($result['token']);

        $this->assertDatabaseHas('activities', [
            'type' => 'api_token_revoked',
        ]);
    }
}
