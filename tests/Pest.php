<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;
use Tests\TestCase;

// Base Pest configuration — expanded in Phase 13.
uses(TestCase::class)->in('Feature', 'Unit');

// Browser (E2E) suite — real Chromium via pest-plugin-browser.
// The default 5s Playwright timeout is too tight for the first cold request
// (kernel boot + first-hit view work), so raise it for the whole suite.
uses(
    TestCase::class,
    RefreshDatabase::class,
)->beforeEach(function () {
    Playwright::setTimeout(30_000);
})->in('Browser');
