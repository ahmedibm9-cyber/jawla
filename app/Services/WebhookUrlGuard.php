<?php

namespace App\Services;

use App\Services\Contracts\DnsResolver;

class WebhookUrlGuard
{
    public function __construct(private readonly DnsResolver $dns) {}

    /** @return array{host:string,port:int,ip:string} */
    public function resolve(string $url): array
    {
        $parts = parse_url($url);
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        throw_unless(($parts['scheme'] ?? null) === 'https' && $host !== '', new \DomainException(
            'Webhook endpoints must use a valid HTTPS URL.',
        ));
        throw_if(isset($parts['user']) || isset($parts['pass']) || preg_match('/^[a-z0-9.-]+$/i', $host) !== 1
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false, new \DomainException(
                'Webhook endpoint credentials and non-ASCII hosts are not allowed.',
            ));

        $addresses = $this->dns->addresses($host);
        throw_if($addresses === [], new \DomainException('Webhook endpoint DNS resolution failed.'));
        foreach ($addresses as $address) {
            throw_unless($this->isPublic($address), new \DomainException(
                'Webhook endpoints cannot resolve to private, local, or reserved addresses.',
            ));
        }

        return ['host' => $host, 'port' => (int) ($parts['port'] ?? 443), 'ip' => $addresses[0]];
    }

    private function isPublic(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP) !== false
            && filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
