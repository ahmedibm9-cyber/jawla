<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;
use Tests\TestCase;

// Base Pest configuration — expanded in Phase 13.
uses(TestCase::class)->in('Feature', 'Unit');

// Browser (E2E) suite — real Chromium via pest-plugin-browser.
//
// Workaround for https://github.com/pestphp/pest/issues#1517
// Windows child-process lifecycle: PDEATHSIG kills the Playwright subprocess
// between tests on dev Windows machines, causing "WebSocket client is not connected’.
// Closing the page between tests prevents accumulation of zombie browser contexts.
uses()->afterEach(function () {
    if (isset($this->page)) {
        $this->page->page()->close();
    }
})->in('Browser');
// The default 5s Playwright timeout is too tight for the first cold request
// (kernel boot + first-hit view work), so raise it for the whole suite.
uses(
    TestCase::class,
    RefreshDatabase::class,
)->beforeEach(function () {
    Playwright::setTimeout(30_000);
})->in('Browser');
