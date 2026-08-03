<?php

namespace App\Support;

final class CsvCell
{
    public static function neutralize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[\x00-\x20=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }
}
