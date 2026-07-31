<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Livewire\App\ProfilePage;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P0-AUTH-02: Password change — wrong current password, min length, requires current.
 *
 * ponytail: RoleSeeder + factories. Livewire testing with direct error checks.
 */
class PasswordChangeTest extends TestCase
{
    use DatabaseTransactions;

    private User $rep;

    private string $originalPassword = 'original-pw-12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $company = Company::factory()->create();
        $this->rep = User::factory()->create(['company_id' => $company->id]);
        $this->rep->assignRole('rep');
        $this->rep->update(['password' => Hash::make($this->originalPassword)]);
    }

    /** Password change with wrong current password is rejected. */
    #[Test]
    public function test_wrong_current_password_rejected(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(ProfilePage::class)
            ->set('name', $this->rep->name)
            ->set('email', $this->rep->email)
            ->set('currentPassword', 'wrong-current-pw')
            ->set('newPassword', 'new-secure-password-123')
            ->set('newPasswordConfirmation', 'new-secure-password-123')
            ->call('save');

        // Password unchanged
        $this->rep->refresh();
        $this->assertTrue(Hash::check($this->originalPassword, $this->rep->password));
    }

    /** Profile update (name/email/phone) works without password change. */
    #[Test]
    public function test_profile_update_succeeds_without_password(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Updated Name')
            ->set('email', $this->rep->email)
            ->set('currentPassword', '')
            ->set('newPassword', '')
            ->set('newPasswordConfirmation', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->rep->refresh();
        $this->assertSame('Updated Name', $this->rep->name);
        $this->assertTrue(Hash::check($this->originalPassword, $this->rep->password));
    }

    /** New password shorter than 8 characters is rejected by validation. */
    #[Test]
    public function test_new_password_too_short_rejected(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(ProfilePage::class)
            ->set('name', $this->rep->name)
            ->set('email', $this->rep->email)
            ->set('currentPassword', $this->originalPassword)
            ->set('newPassword', 'short')
            ->set('newPasswordConfirmation', 'short')
            ->call('save')
            ->assertHasErrors(['newPassword']);
    }

    /** Password change without current password is rejected when newPassword is set. */
    #[Test]
    public function test_password_change_requires_current_password(): void
    {
        $this->actingAs($this->rep);

        Livewire::test(ProfilePage::class)
            ->set('name', $this->rep->name)
            ->set('email', $this->rep->email)
            ->set('currentPassword', '')
            ->set('newPassword', 'new-secure-password-123')
            ->set('newPasswordConfirmation', 'new-secure-password-123')
            ->call('save');

        // Password should not have changed
        $this->rep->refresh();
        $this->assertTrue(Hash::check($this->originalPassword, $this->rep->password));
    }
}
