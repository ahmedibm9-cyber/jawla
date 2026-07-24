<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * US-23.1 — Edit Profile
 *
 * Tests that the rep profile page renders and supports name/email/password changes.
 */
class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_profile_page_renders_for_rep(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/app/profile')->assertOk();
    }

    public function test_profile_page_shows_current_user_data(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(\App\Livewire\App\ProfilePage::class)
            ->assertSuccessful();
    }

    public function test_profile_update_requires_current_password_for_new_password(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(\App\Livewire\App\ProfilePage::class)
            ->set('name', 'Updated Name')
            ->set('email', 'updated@test.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $rep->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_profile_update_with_correct_password(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        Livewire::test(\App\Livewire\App\ProfilePage::class)
            ->set('name', 'Updated Rep Name')
            ->set('currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $rep->id,
            'name' => 'Updated Rep Name',
        ]);
    }

    public function test_rep_cannot_access_admin_panel(): void
    {
        $rep = User::where('email', 'rep@jawla.test')->first();
        $this->actingAs($rep);

        $this->get('/admin')->assertRedirect();
    }
}
