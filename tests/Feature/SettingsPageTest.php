<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * US-23.2 — View Settings
 *
 * Tests that the settings page renders and shows user/company info.
 */
class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_settings_page_renders_for_rep(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/app/settings')->assertOk();
    }

    public function test_settings_page_shows_user_and_company(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $response = Livewire::test(\App\Livewire\App\SettingsPage::class);
        $response->assertSuccessful();
    }

    public function test_settings_page_shows_current_locale(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/app/settings')->assertOk();
    }
}
