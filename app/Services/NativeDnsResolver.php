<?php

namespace App\Services;

use App\Services\Contracts\DnsResolver;

class NativeDnsResolver implements DnsResolver
{
    public function addresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];
        foreach ($records ?: [] as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        foreach (gethostbynamel($host) ?: [] as $address) {
            $addresses[] = $address;
        }

        return array_values(array_unique($addresses));
    }
}
