<?php

declare(strict_types=1);

namespace Tests\Feature\SystemPage;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPageAccessibilityTest extends TestCase
{
    #[DataProvider('errorViews')]
    public function test_error_pages_have_landmarks_visible_brand_and_overflow_safe_skip_links(string $view): void
    {
        app()->setLocale('en');

        $html = view($view)->render();

        $this->assertStringContainsString('<main id="main">', $html);
        $this->assertStringContainsString('/images/green-j.webp', $html);
        $this->assertStringContainsString('class="skip-link"', $html);
        $this->assertStringContainsString('clip:rect(0,0,0,0)', $html);
        $this->assertStringNotContainsString('left:-9999px', $html);
        $this->assertStringContainsString('box-sizing:border-box', $html);
    }

    #[DataProvider('errorViews')]
    public function test_error_page_navigation_labels_are_localized_for_rtl(string $view): void
    {
        app()->setLocale('ar');

        $html = view($view)->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('تخطى إلى المحتوى', $html);
        $this->assertStringContainsString('aria-label="الرئيسية"', $html);
    }

    /** @return array<string, array{string}> */
    public static function errorViews(): array
    {
        return [
            'forbidden' => ['errors.403'],
            'not-found' => ['errors.404'],
            'expired' => ['errors.419'],
            'server-error' => ['errors.500'],
        ];
    }
}
