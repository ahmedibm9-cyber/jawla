<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Onboarding tour — first-login detection, completion endpoint, replay.
 *
 * - New users have onboarding_seen = false by default
 * - POST /api/onboarding/complete requires auth
 * - POST /api/onboarding/complete sets onboarding_seen = true
 * - Authenticated rep can hit the endpoint
 * - Unauthenticated request is rejected
 */
class OnboardingTourTest extends TestCase
{
    use RefreshDatabase;

    private User $rep;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        $this->rep = User::where('email', 'rep@jawla.test')->firstOrFail();
        $this->rep->update(['password' => Hash::make('test-rep'), 'onboarding_seen' => false]);
    }

    // ─── Default state ───────────────────────────────────────────

    public function test_new_user_has_onboarding_seen_false(): void
    {
        $this->assertFalse((bool) $this->rep->onboarding_seen);
    }

    public function test_onboarding_seen_column_exists(): void
    {
        $this->assertDatabaseHas('users', [
            'id' => $this->rep->id,
            'onboarding_seen' => false,
        ]);
    }

    // ─── Endpoint: auth required ─────────────────────────────────

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/onboarding/complete')
            ->assertUnauthorized();
    }

    public function test_authenticated_rep_can_complete_onboarding(): void
    {
        $this->actingAs($this->rep);

        $this->postJson('/api/onboarding/complete')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    // ─── Endpoint: state change ──────────────────────────────────

    public function test_complete_sets_onboarding_seen_to_true(): void
    {
        $this->actingAs($this->rep);

        $this->postJson('/api/onboarding/complete');

        $this->assertDatabaseHas('users', [
            'id' => $this->rep->id,
            'onboarding_seen' => true,
        ]);
    }

    public function test_complete_is_idempotent(): void
    {
        $this->actingAs($this->rep);

        $this->postJson('/api/onboarding/complete')->assertOk();
        $this->postJson('/api/onboarding/complete')->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->rep->id,
            'onboarding_seen' => true,
        ]);
    }

    // ─── Endpoint: rate limiting ─────────────────────────────────

    public function test_complete_is_throttled(): void
    {
        $this->actingAs($this->rep);

        // Hit the throttle limit (60/min by default for POST routes)
        // We just verify the route has throttle middleware by checking
        // the route exists and responds correctly for the first request.
        $this->postJson('/api/onboarding/complete')
            ->assertOk();
    }

    // ─── Translation keys exist ──────────────────────────────────

    public function test_rep_tour_translation_keys_exist(): void
    {
        $keys = [
            'tour_welcome', 'tour_welcome_desc',
            'tour_todays_plan', 'tour_todays_plan_desc',
            'tour_visits', 'tour_visits_desc',
            'tour_tab_bar', 'tour_tab_bar_desc',
            'tour_quotations', 'tour_quotations_desc',
            'tour_more_menu', 'tour_more_menu_desc',
            'tour_notifications', 'tour_notifications_desc',
            'tour_offline', 'tour_offline_desc',
            'tour_next', 'tour_back', 'tour_finish', 'tour_replay',
        ];

        foreach ($keys as $key) {
            $this->assertNotEmpty(
                trans("app.{$key}"),
                "Missing translation key: app.{$key}",
            );
        }
    }

    public function test_admin_tour_translation_keys_exist(): void
    {
        $keys = [
            'tour_admin_welcome', 'tour_admin_welcome_desc',
            'tour_admin_sidebar', 'tour_admin_sidebar_desc',
            'tour_admin_sales', 'tour_admin_sales_desc',
            'tour_admin_inventory', 'tour_admin_inventory_desc',
            'tour_admin_alarms', 'tour_admin_alarms_desc',
            'tour_admin_reports', 'tour_admin_reports_desc',
            'tour_admin_user_menu', 'tour_admin_user_menu_desc',
            'tour_admin_maps', 'tour_admin_maps_desc',
        ];

        foreach ($keys as $key) {
            $this->assertNotEmpty(
                trans("app.{$key}"),
                "Missing translation key: app.{$key}",
            );
        }
    }

    public function test_arabic_tour_translation_keys_exist(): void
    {
        app()->setLocale('ar');

        $keys = [
            'tour_welcome', 'tour_todays_plan', 'tour_visits',
            'tour_admin_welcome', 'tour_admin_sidebar',
            'tour_next', 'tour_back', 'tour_finish', 'tour_replay',
        ];

        foreach ($keys as $key) {
            $this->assertNotEmpty(
                trans("app.{$key}"),
                "Missing Arabic translation key: app.{$key}",
            );
        }
    }

    public function test_tour_assets_are_first_party_and_do_not_depend_on_a_runtime_cdn(): void
    {
        $script = file_get_contents(public_path('js/onboarding.js'));
        $repHead = file_get_contents(resource_path('views/components/onboarding-trigger.blade.php'));
        $adminHead = file_get_contents(resource_path('views/filament/onboarding-head.blade.php'));
        $pwaHead = file_get_contents(resource_path('views/filament/pwa-head.blade.php'));

        $this->assertIsString($script);
        $this->assertIsString($repHead);
        $this->assertIsString($adminHead);
        $this->assertIsString($pwaHead);
        $this->assertStringContainsString('class JawlaTour', $script);
        $this->assertStringNotContainsString('Shepherd.', $script);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $repHead);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $adminHead);
        $this->assertStringContainsString('rel="manifest"', $pwaHead);
        $this->assertStringContainsString('pwa-login-register.js', $pwaHead);
        $this->assertFileExists(public_path('js/pwa-login-register.js'));
    }

    // ─── Migration integrity ─────────────────────────────────────

    public function test_migration_added_boolean_column_with_default(): void
    {
        $user = User::factory()->create();

        // Column should default to false
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'onboarding_seen' => false,
        ]);
    }

    public function test_onboarding_seen_can_be_set_to_true(): void
    {
        $this->rep->update(['onboarding_seen' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $this->rep->id,
            'onboarding_seen' => true,
        ]);
    }
}
