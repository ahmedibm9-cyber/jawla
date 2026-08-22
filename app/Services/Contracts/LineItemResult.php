<?php

namespace App\Services\Contracts;

class LineItemResult
{
    public function __construct(
        public readonly string $lineTotal,
        public readonly string $vatAmount,
        public readonly bool $vatApplicable,
    ) {}
}
