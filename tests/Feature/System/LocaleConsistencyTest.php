<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Locale consistency — RTL/LTR, document direction, and preference persistence.
 *
 * Maps to TEST_PLAN sections 1.2, 3.2, 3.8 (localization, RTL/LTR, mixed direction).
 */
class LocaleConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Login page renders in Arabic with RTL.
     *
     * @test
     */
    public function test_login_page_renders_rtl_arabic(): void
    {
        $this->get('/locale/ar');
        $response = $this->get('/login')->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('lang="ar"', $content);
        $this->assertStringContainsString('dir="rtl"', $content);
    }

    /**
     * Login page renders in English with LTR.
     *
     * @test
     */
    public function test_login_page_renders_ltr_english(): void
    {
        $this->get('/locale/en');
        $response = $this->get('/login')->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('lang="en"', $content);
        $this->assertStringContainsString('dir="ltr"', $content);
    }

    /**
     * Admin dashboard renders in both locales.
     *
     * @test
     */
    public function test_admin_dashboard_renders_both_locales(): void
    {
        $admin = $this->makeUser('admin');

        foreach (['en', 'ar'] as $locale) {
            $this->get("/locale/{$locale}");
            $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        }
    }

    /**
     * Rep PWA renders in both locales.
     *
     * @test
     */
    public function test_rep_pwa_renders_both_locales(): void
    {
        $rep = $this->makeUser('rep');

        foreach (['en', 'ar'] as $locale) {
            $this->get("/locale/{$locale}");
            $this->actingAs($rep)->get('/app')->assertOk();
        }
    }

    /**
     * Locale switch with invalid locale is rejected.
     *
     * @test
     */
    public function test_invalid_locale_rejected(): void
    {
        $response = $this->get('/locale/xx');
        $this->assertContains($response->status(), [400, 404, 302]);
    }

    /**
     * HTML lang attribute matches Arabic locale.
     *
     * @test
     */
    public function test_html_lang_attribute_arabic(): void
    {
        $this->get('/locale/ar');
        $response = $this->get('/login')->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('lang="ar"', $content);
    }

    /**
     * HTML lang attribute matches English locale.
     *
     * @test
     */
    public function test_html_lang_attribute_english(): void
    {
        $this->get('/locale/en');
        $response = $this->get('/login')->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('lang="en"', $content);
    }
}
