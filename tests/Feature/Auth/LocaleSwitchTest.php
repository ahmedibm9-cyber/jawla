<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_locale_changes_session(): void
    {
        $this->get('/locale/en');
        $this->assertSame('en', session('locale'));

        $this->get('/locale/ar');
        $this->assertSame('ar', session('locale'));
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this->get('/locale/xx');

        $response->assertNotFound();
    }
}
