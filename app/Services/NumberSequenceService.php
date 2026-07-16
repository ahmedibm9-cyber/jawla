<?php

namespace App\Services;

class NumberSequenceService implements Contracts\DocumentNumberService
{
    public function generate(string $docType, int $companyId): string
    {
        // Implemented in Phase 8 with naming_series table + row-level lock.
        return $docType.'-'.$companyId.'-'.date('Y').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
