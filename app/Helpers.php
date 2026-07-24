<?php

if (! function_exists('l')) {
    function l(string $arabic, string $english): string
    {
        return app()->getLocale() === 'ar' ? $arabic : $english;
    }
}
