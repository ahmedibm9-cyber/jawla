<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_locale_changes_session_and_direction(): void
    {
        $this->get('/locale/en');
        $this->assertSame('en', session('locale'));

        $response = $this->get('/app/login');
        $response->assertSee('dir="ltr"', false);

        $this->get('/locale/ar');
        $this->assertSame('ar', session('locale'));

        $response = $this->get('/app/login');
        $response->assertSee('dir="rtl"', false);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this->get('/locale/xx');

        $response->assertNotFound();
    }
}
