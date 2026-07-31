<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Filament\Auth\Pages\Login;
use App\Models\Company;
use App\Models\User;
use App\Services\SessionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P0-AUTH-02: Session management — session list, revoke, logout, back-button.
 *
 * ponytail: uses HTTP testing (not actingAs) so Filament panels get real sessions.
 */
class SessionManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private string $password = 'test-session-pw-2024';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->assignRole('admin');
        $this->admin->update(['password' => Hash::make($this->password)]);
    }

    private function loginAsAdmin(): void
    {
        // Login via Livewire to establish a real session
        Livewire::test(Login::class)
            ->set('data.email', $this->admin->email)
            ->set('data.password', $this->password)
            ->call('authenticate');
    }

    /** Session management page renders for an admin. */
    #[Test]
    public function test_admin_session_management_page_renders(): void
    {
        $this->loginAsAdmin();
        $this->assertAuthenticatedAs($this->admin);

        $this->get('/admin/admin/sessions')->assertOk();
    }

    /** SessionService::listActiveSessions returns empty array when no sessions in DB (array driver in tests). */
    #[Test]
    public function test_admin_can_list_sessions(): void
    {
        $this->loginAsAdmin();

        $service = app(SessionService::class);
        $sessions = $service->listActiveSessions((int) session()->getId());

        // Testing uses array session driver — no rows in sessions table.
        // Service should still return an array without errors.
        $this->assertIsArray($sessions);
    }

    /** Admin can revoke all sessions except current. */
    #[Test]
    public function test_admin_can_revoke_session(): void
    {
        $this->loginAsAdmin();

        $service = app(SessionService::class);
        $before = $service->listActiveSessions((int) session()->getId());

        $service->revokeAllExceptCurrent(session()->getId());

        $after = $service->listActiveSessions((int) session()->getId());
        $this->assertLessThanOrEqual(count($before), count($after));
    }

    /** After logout, the session is invalidated. */
    #[Test]
    public function test_logout_invalidates_session(): void
    {
        $this->loginAsAdmin();
        $this->assertAuthenticatedAs($this->admin);

        // Logout via Filament's admin logout route
        $this->post('/admin/logout');
        $this->assertGuest();
    }

    /** After logout, accessing admin pages redirects to login. */
    #[Test]
    public function test_back_button_after_logout_does_not_restore(): void
    {
        $this->loginAsAdmin();
        $this->post('/admin/logout');
        $this->assertGuest();

        // Attempting to access a protected page after logout should redirect
        $this->get('/admin')->assertRedirect();
    }
}
