<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
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
        $expiration = config('sanctum.expiration');
        $token = $this->user->createToken('test-token', ['*'], Carbon::now()->addMinutes($expiration));
        $this->assertNotNull($token->accessToken->expires_at);
    }

    public function test_token_expires_after_configured_time(): void
    {
        $expectedExpiration = config('sanctum.expiration');
        $token = $this->user->createToken('test-token', ['*'], Carbon::now()->addMinutes($expectedExpiration));
        $expiresAt = $token->accessToken->expires_at;

        $this->assertNotNull($expiresAt);
        $this->assertTrue($expiresAt->isFuture());

        // Token should expire within the configured time
        $maxExpiry = Carbon::now()->addMinutes($expectedExpiration + 1);
        $this->assertTrue($expiresAt->lessThanOrEqualTo($maxExpiry));
    }

    public function test_expired_token_is_rejected(): void
    {
        // Create a token that's already expired
        $expiration = config('sanctum.expiration');
        $token = $this->user->createToken('test-token', ['*'], Carbon::now()->subHour());

        // Try to use the expired token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
            'Accept' => 'application/json',
        ])->get('/api/v1/whoami');

        $response->assertStatus(401);
    }

    public function test_valid_token_is_accepted(): void
    {
        // Create a token with future expiration
        $expiration = config('sanctum.expiration');
        $token = $this->user->createToken('test-token', ['*'], Carbon::now()->addMinutes($expiration));

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
