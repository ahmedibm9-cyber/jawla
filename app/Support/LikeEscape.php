<?php

namespace App\Support;

/**
 * Escapes LIKE wildcard characters in user input to prevent
 * filter-bypass via %, _, or \ in search terms.
 */
final class LikeEscape
{
    public static function escape(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }

    public static function wrap(string $value): string
    {
        return '%'.self::escape($value).'%';
    }
}
