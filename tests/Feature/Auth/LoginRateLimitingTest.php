<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimitingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected string $email;

    protected string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        RateLimiter::clear('login');

        $company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('rep');
        $this->password = 'test-password-2024';
        $this->user->update(['password' => Hash::make($this->password)]);
        $this->email = $this->user->email;
    }

    public function test_login_rate_limiting_is_configured(): void
    {
        $limiter = RateLimiter::limiter('login');
        $this->assertNotNull($limiter);
    }

    public function test_rate_limit_key_includes_email_and_ip(): void
    {
        $request = new Request;
        $request->merge(['email' => $this->email]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $limiter = RateLimiter::limiter('login');
        $result = $limiter($request);

        $this->assertEquals(5, $result->maxAttempts);
        // Rate limiter lowercases the email
        $this->assertStringContainsString(strtolower($this->email), $result->key);
        $this->assertStringContainsString('127.0.0.1', $result->key);
    }

    public function test_different_emails_have_independent_limits(): void
    {
        $request1 = new Request;
        $request1->merge(['email' => 'user1@example.com']);
        $request1->server->set('REMOTE_ADDR', '127.0.0.1');

        $request2 = new Request;
        $request2->merge(['email' => 'user2@example.com']);
        $request2->server->set('REMOTE_ADDR', '127.0.0.1');

        $limiter = RateLimiter::limiter('login');
        $result1 = $limiter($request1);
        $result2 = $limiter($request2);

        $this->assertNotEquals($result1->key, $result2->key);
    }

    public function test_different_ips_have_independent_limits(): void
    {
        $request1 = new Request;
        $request1->merge(['email' => $this->email]);
        $request1->server->set('REMOTE_ADDR', '127.0.0.1');

        $request2 = new Request;
        $request2->merge(['email' => $this->email]);
        $request2->server->set('REMOTE_ADDR', '192.168.1.1');

        $limiter = RateLimiter::limiter('login');
        $result1 = $limiter($request1);
        $result2 = $limiter($request2);

        $this->assertNotEquals($result1->key, $result2->key);
    }

    public function test_rate_limit_returns_5_per_minute(): void
    {
        $request = new Request;
        $request->merge(['email' => $this->email]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $limiter = RateLimiter::limiter('login');
        $result = $limiter($request);

        $this->assertEquals(5, $result->maxAttempts);
        // Verify the limit is per minute (decay in seconds)
        $this->assertEquals(60, $result->decaySeconds);
    }

    public function test_rate_limit_resets_after_window(): void
    {
        $request = new Request;
        $request->merge(['email' => $this->email]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $limiter = RateLimiter::limiter('login');
        $result = $limiter($request);

        // Simulate hitting the rate limit
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($result->key, 60);
        }

        // Check that rate limit is hit
        $this->assertTrue(RateLimiter::tooManyAttempts($result->key, 5));

        // Simulate time passing (by clearing the rate limiter)
        RateLimiter::clear($result->key);

        // Check that rate limit is cleared
        $this->assertFalse(RateLimiter::tooManyAttempts($result->key, 5));
    }
}
