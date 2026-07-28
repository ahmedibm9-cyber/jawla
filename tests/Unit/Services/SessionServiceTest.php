<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_user_on_shift_returns_true_when_active(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        DB::table('work_sessions')->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'started_at' => now(),
            'ended_at' => null,
            'start_latitude' => 30.0444,
            'start_longitude' => 31.2357,
        ]);

        $this->assertTrue(app(SessionService::class)->isUserOnShift($user->id));
    }

    public function test_is_user_on_shift_returns_false_when_off_shift(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertFalse(app(SessionService::class)->isUserOnShift($user->id));
    }

    public function test_is_user_on_shift_returns_false_when_shift_ended(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        DB::table('work_sessions')->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'started_at' => now()->subHours(8),
            'ended_at' => now()->subHour(),
            'start_latitude' => 30.0444,
            'start_longitude' => 31.2357,
        ]);

        $this->assertFalse(app(SessionService::class)->isUserOnShift($user->id));
    }

    public function test_revoke_session_deletes_session(): void
    {
        $sessionId = 'test-session-abc';
        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => 1,
            'payload' => '',
            'last_activity' => time(),
        ]);

        $deleted = app(SessionService::class)->revokeSession($sessionId);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    public function test_revoke_all_except_current_preserves_current(): void
    {
        $currentId = 'current-session';
        $otherId = 'other-session';

        DB::table('sessions')->insert([
            ['id' => $currentId, 'user_id' => 1, 'payload' => '', 'last_activity' => time()],
            ['id' => $otherId, 'user_id' => 2, 'payload' => '', 'last_activity' => time()],
        ]);

        $deleted = app(SessionService::class)->revokeAllExceptCurrent($currentId);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseHas('sessions', ['id' => $currentId]);
        $this->assertDatabaseMissing('sessions', ['id' => $otherId]);
    }
}
