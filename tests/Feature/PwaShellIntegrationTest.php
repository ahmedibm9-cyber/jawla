<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaShellIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_includes_the_pwa_bootstrap_assets(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('rel="manifest" href="/manifest.json"', false)
            ->assertSee('name="theme-color" content="#0F172A"', false)
            ->assertSee('rel="apple-touch-icon" href="/icons/icon-192.png"', false)
            ->assertSee('pwa-login-register.js', false);
    }

    public function test_rep_shell_exposes_an_accessible_storage_pressure_recovery_link(): void
    {
        $this->seed(DemoSeeder::class);
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        $this->actingAs($rep)->get('/app')
            ->assertOk()
            ->assertSee('id="storage-pressure-indicator"', false)
            ->assertSee('href="/app/sync-queue"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="assertive"', false)
            ->assertSee('Device storage is low', false);
    }

    public function test_rep_shell_localizes_storage_pressure_recovery_for_rtl_users(): void
    {
        $this->seed(DemoSeeder::class);
        $rep = User::where('email', 'rep@jawla.test')->firstOrFail();

        $this->withSession(['locale' => 'ar'])->actingAs($rep)->get('/app')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('مساحة الجهاز منخفضة', false);
    }

    public function test_offline_fallback_uses_a_safe_retry_link(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertSee('class="btn" href="/app"', false)
            ->assertSee('Retry');
    }
}
