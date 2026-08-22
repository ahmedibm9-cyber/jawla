<?php

namespace App\Services\Contracts;

class LineItemInput
{
    public function __construct(
        public readonly string $qty,
        public readonly string $unitPrice,
        public readonly bool $vatApplicable,
    ) {}
}
