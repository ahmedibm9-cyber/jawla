<?php

namespace Tests\Feature;

use App\Filament\Pages\ApiTokens;
use App\Models\Company;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use App\Support\ApiAbilities;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokensPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->assignRole('admin');
        app(ActiveCompanyContext::class)->setFromUser($this->admin);
    }

    protected function tearDown(): void
    {
        app(ActiveCompanyContext::class)->disable();
        parent::tearDown();
    }

    public function test_only_admin_can_access(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(ApiTokens::canAccess());

        $rep = User::factory()->create(['company_id' => $this->admin->company_id]);
        $rep->assignRole('rep');
        $this->actingAs($rep);
        $this->assertFalse(ApiTokens::canAccess());
    }

    public function test_admin_creates_a_scoped_token_and_sees_plaintext_once(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ApiTokens::class)
            ->assertOk()
            ->set('tokenName', 'Odoo integration')
            ->set('selectedAbilities', [ApiAbilities::READ_PRODUCTS])
            ->call('createToken')
            ->assertHasNoErrors()
            ->assertSet('plainTextToken', fn ($v) => is_string($v) && $v !== '')
            ->assertSet('tokenName', '');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->admin->id,
            'name' => 'Odoo integration',
        ]);
    }

    public function test_create_validates_name_and_abilities(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ApiTokens::class)
            ->set('tokenName', '')
            ->set('selectedAbilities', [])
            ->call('createToken')
            ->assertHasErrors(['tokenName', 'selectedAbilities']);
    }

    public function test_admin_revokes_a_token(): void
    {
        $token = $this->admin->createToken('to-revoke', [ApiAbilities::READ_PRODUCTS])->accessToken;

        Livewire::actingAs($this->admin)
            ->test(ApiTokens::class)
            ->call('revokeToken', $token->id);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }
}
