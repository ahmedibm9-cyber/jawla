<?php

namespace App\Services\Contracts;

interface DocumentNumberService
{
    public function generate(string $docType, int $companyId): string;
}
