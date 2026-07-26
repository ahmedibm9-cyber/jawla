<?php

namespace App\Models\Concerns;

use App\Exceptions\Domain\DomainException;

trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::deleting(function (): never {
            throw new DomainException('Ledger records are append-only and cannot be deleted.');
        });
    }
}
