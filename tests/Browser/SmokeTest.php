<?php

it('loads the admin login page in a real browser', function () {
    $page = visit('/admin/login');

    $page->assertNoJavascriptErrors();
});
