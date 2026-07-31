<?php

declare(strict_types=1);

namespace Tests\Feature\SystemPage;

use Tests\TestCase;

class OfflinePageTest extends TestCase
{
    public function test_offline_page_has_a_visible_landmark_brand_and_recovery_action_in_english(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/offline')
            ->assertOk()
            ->assertSee('<main class="card">', false)
            ->assertSee('/images/green-j.webp', false)
            ->assertSee('No Internet Connection')
            ->assertSee('href="/app"', false)
            ->assertSee('Retry');
    }

    public function test_offline_page_is_localized_and_rtl_in_arabic(): void
    {
        $this->withSession(['locale' => 'ar'])
            ->get('/offline')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('لا يوجد اتصال بالإنترنت')
            ->assertSee('إعادة المحاولة');
    }
}
