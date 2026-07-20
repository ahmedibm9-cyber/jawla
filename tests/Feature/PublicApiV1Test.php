<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Support\ApiAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Public API v1: authentication, ability (scope) gating, company scoping, and
 * the token-issuance service. Uses real Sanctum tokens over Bearer headers so
 * the genuine CheckForAnyAbility path is exercised (Sanctum::actingAs mocks
 * can() and cannot faithfully deny an ability).
 */
class PublicApiV1Test extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    private function bearer(array $abilities): string
    {
        return $this->user->createToken('itest', $abilities)->plainTextToken;
    }

    public function test_whoami_requires_authentication(): void
    {
        $this->getJson('/api/v1/whoami')->assertStatus(401);
    }

    public function test_whoami_returns_identity_and_abilities(): void
    {
        $token = $this->bearer([ApiAbilities::READ_PRODUCTS]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/whoami')
            ->assertOk()
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.company_id', $this->company->id)
            ->assertJsonPath('data.abilities', [ApiAbilities::READ_PRODUCTS])
            ->assertJsonPath('data.available_abilities', ApiAbilities::keys());
    }

    public function test_products_forbidden_without_read_products_ability(): void
    {
        Product::factory()->create(['company_id' => $this->company->id]);
        $token = $this->bearer([ApiAbilities::READ_CUSTOMERS]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products')
            ->assertStatus(403);
    }

    public function test_products_allowed_with_read_products_ability(): void
    {
        Product::factory()->create(['company_id' => $this->company->id]);
        $token = $this->bearer([ApiAbilities::READ_PRODUCTS]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'sku', 'name_ar', 'name_en', 'price']], 'meta', 'links']);
    }

    public function test_products_are_scoped_to_the_token_owners_company(): void
    {
        $mine = Product::factory()->create(['company_id' => $this->company->id, 'name_en' => 'Mine']);
        $other = Company::factory()->create();
        Product::factory()->create(['company_id' => $other->id, 'name_en' => 'Theirs']);

        $token = $this->bearer([ApiAbilities::READ_PRODUCTS]);
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products')
            ->assertOk();

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($mine->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_customers_forbidden_without_read_customers_ability(): void
    {
        Customer::factory()->create(['company_id' => $this->company->id]);
        $token = $this->bearer([ApiAbilities::READ_PRODUCTS]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/customers')
            ->assertStatus(403);
    }

    public function test_customers_allowed_with_read_customers_ability(): void
    {
        Customer::factory()->create(['company_id' => $this->company->id]);
        $token = $this->bearer([ApiAbilities::READ_CUSTOMERS]);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/customers')
            ->assertOk();
    }

    public function test_token_service_issues_token_with_abilities_and_audits(): void
    {
        $result = app(ApiTokenService::class)->issue($this->user, 'Integration A', [ApiAbilities::READ_PRODUCTS]);

        $this->assertNotEmpty($result['plainTextToken']);
        $this->assertTrue($result['token']->can(ApiAbilities::READ_PRODUCTS));
        $this->assertFalse($result['token']->can(ApiAbilities::READ_CUSTOMERS));
        $this->assertDatabaseHas('activities', ['type' => 'api_token_issued']);
    }

    public function test_token_service_rejects_unknown_ability(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(ApiTokenService::class)->issue($this->user, 'Bad', ['read:everything']);
    }

    public function test_token_service_rejects_empty_abilities(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(ApiTokenService::class)->issue($this->user, 'Empty', []);
    }

    public function test_token_service_revokes_and_audits(): void
    {
        $issued = app(ApiTokenService::class)->issue($this->user, 'ToRevoke', [ApiAbilities::READ_PRODUCTS]);
        /** @var PersonalAccessToken $token */
        $token = $issued['token'];

        app(ApiTokenService::class)->revoke($token);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
        $this->assertTrue(Activity::where('type', 'api_token_revoked')->exists());
    }
}
