<?php

it('loads the offline fallback page without JavaScript errors', function () {
    $page = visit('/offline');

    $page->assertNoJavascriptErrors();
});

it('renders the offline page in Arabic RTL', function () {
    $page = $this->withSession(['locale' => 'ar'])->visit('/offline');

    $page->assertNoJavascriptErrors()
        ->assertSourceHas('dir="rtl"')
        ->assertSee('لا يوجد اتصال بالإنترنت');
});

it('renders the offline page in English LTR', function () {
    $page = $this->withSession(['locale' => 'en'])->visit('/offline');

    $page->assertNoJavascriptErrors()
        ->assertSourceHas('dir="ltr"')
        ->assertSee('No Internet Connection');
});

it('has a retry button on the offline page', function () {
    $page = visit('/offline');

    $page->assertNoJavascriptErrors()
        ->assertPresent('.btn');
});
