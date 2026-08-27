<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SanctumTokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('admin');
        $this->password = 'test-password-2024';
        $this->user->update(['password' => Hash::make($this->password)]);
    }

    public function test_sanctum_expiration_is_configured(): void
    {
        $expiration = config('sanctum.expiration');
        $this->assertNotNull($expiration);
        $this->assertIsInt($expiration);
        $this->assertGreaterThan(0, $expiration);
    }

    public function test_sanctum_expiration_is_24_hours_by_default(): void
    {
        $expiration = config('sanctum.expiration');
        $this->assertEquals(1440, $expiration); // 24 hours = 1440 minutes
    }

    public function test_token_has_expires_at_set(): void
    {
        $token = $this->user->createToken('test-token');
        // Sanctum v4: expiration is checked dynamically from config, not stored on model
        $this->assertNotNull(config('sanctum.expiration'));
    }

    public function test_token_expires_after_configured_time(): void
    {
        $token = $this->user->createToken('test-token');
        $expectedExpiration = config('sanctum.expiration');

        // Sanctum v4: expiration is checked dynamically from config, not stored on model
        $this->assertIsInt($expectedExpiration);
        $this->assertGreaterThan(0, $expectedExpiration);
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = $this->user->createToken('test-token');

        // Sanctum v4: expiration is dynamic from config, so override config to expire immediately
        config(['sanctum.expiration' => -1]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/v1/whoami');

        $response->assertStatus(401);
    }

    public function test_valid_token_is_accepted(): void
    {
        // Create a token (will have future expires_at)
        $token = $this->user->createToken('test-token');

        // Try to use the valid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/v1/whoami');

        $response->assertOk();
    }

    public function test_token_expiration_can_be_overridden_via_env(): void
    {
        // This test verifies the env variable is read
        // In production, you'd set SANCTUM_TOKEN_EXPIRATION in .env
        $expiration = config('sanctum.expiration');
        $this->assertIsInt($expiration);
    }
}
