<?php

namespace App\Services\Contracts;

interface DnsResolver
{
    /** @return list<string> */
    public function addresses(string $host): array;
}
