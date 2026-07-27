<?php

namespace App\Services\Contracts;

interface DocumentNumberService
{
    /**
     * Allocate the next gapless legal number for $docType in $companyId's
     * scope, optionally for a specific calendar $year (defaults to current).
     */
    public function generate(string $docType, int $companyId, ?int $year = null): string;
}
