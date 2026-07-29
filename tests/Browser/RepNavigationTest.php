<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * End-to-end browser tests for rep navigation, auth flow,
 * customer creation, and settings/profile pages.
 */
function makeRepForNav(): User
{
    test()->seed(RoleSeeder::class);
    $company = Company::factory()->create(['name_ar' => 'شركة التنقل']);
    $rep = User::factory()->create([
        'company_id' => $company->id,
        'name' => 'مندوب التنقل',
        'onboarding_seen' => true,
    ]);
    $rep->assignRole('rep');

    return $rep;
}

it('starts the first-party onboarding tour for a new rep', function () {
    $rep = makeRepForNav();
    $rep->update(['onboarding_seen' => false]);

    $this->actingAs($rep)->visit('/app')
        ->assertNoJavascriptErrors()
        ->assertPresent('.jawla-tour-overlay')
        ->assertPresent('.shepherd-element[role="dialog"]');
});

it('loads the add customer page without JavaScript errors', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/customers/create');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.add_customer'));
});

it('renders the add customer form fields', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/customers/create');

    $page->assertNoJavascriptErrors()
        ->assertPresent('input[name="name_ar"]')
        ->assertPresent('input[name="name_en"]')
        ->assertPresent('input[name="phone"]');
});

it('loads the complaints page without JavaScript errors', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/complaints');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.complaints'));
});

it('loads the profile page without JavaScript errors', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/profile');

    $page->assertNoJavascriptErrors()
        ->assertSee($rep->name);
});

it('loads the settings page without JavaScript errors', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/settings');

    $page->assertNoJavascriptErrors();
});

it('loads the more page without JavaScript errors', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/more');

    $page->assertNoJavascriptErrors();
});

it('loads the visits page without JavaScript errors', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app/visits');

    $page->assertNoJavascriptErrors()
        ->assertSee(__('app.visits'));
});

it('renders the home page with tab bar navigation', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/app');

    $page->assertNoJavascriptErrors()
        ->assertPresent('[role="navigation"]');
});

it('admin login page loads and shows login form', function () {
    test()->seed(RoleSeeder::class);

    $page = visit('/admin/login');

    $page->assertNoJavascriptErrors()
        ->assertPresent('input[type="email"]')
        ->assertPresent('input[type="password"]');
});

it('health endpoint returns OK', function () {
    $response = $this->get('/health');

    $response->assertStatus(200);
});

it('locale switcher works for Arabic', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/locale/ar');

    $page->assertNoJavascriptErrors();
});

it('locale switcher works for English', function () {
    $rep = makeRepForNav();

    $page = $this->actingAs($rep)->visit('/locale/en');

    $page->assertNoJavascriptErrors();
});
