<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Sentry before_send scrubber. Masks sensitive values before an event leaves the
 * app so credentials, tokens, and financial secrets never reach the error
 * dashboard. Referenced from config/sentry.php as a string callable
 * ('App\Support\SentryScrubber::handle') so it survives `config:cache` — a
 * closure there would make the cached config non-serializable.
 *
 * This complements send_default_pii=false and sql_bindings=false: those keep IP/
 * cookies/query-params out; this catches sensitive keys in request bodies and
 * extra context.
 */
class SentryScrubber
{
    /** Key fragments (case-insensitive) whose values are masked wherever they appear. */
    private const SENSITIVE = [
        'password', 'secret', 'token', 'authorization', 'cookie', 'api_key',
        'apikey', 'csrf', 'xsrf', 'zatca_secret', 'zatca_csid', 'private',
        'session', 'credential',
    ];

    private const MASK = '[filtered]';

    public static function handle(Event $event, ?EventHint $hint): ?Event
    {
        $request = $event->getRequest();
        if (! empty($request)) {
            if (isset($request['data']) && is_array($request['data'])) {
                $request['data'] = self::scrub($request['data']);
            }
            if (isset($request['cookies'])) {
                $request['cookies'] = self::MASK;
            }
            if (isset($request['headers']) && is_array($request['headers'])) {
                $request['headers'] = self::scrub($request['headers']);
            }
            $event->setRequest($request);
        }

        $extra = $event->getExtra();
        if (! empty($extra)) {
            $event->setExtra(self::scrub($extra));
        }

        return $event;
    }

    /**
     * Recursively mask values whose key contains a sensitive fragment.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (self::isSensitive($key)) {
                $data[$key] = self::MASK;

                continue;
            }
            if (is_array($value)) {
                $data[$key] = self::scrub($value);
            }
        }

        return $data;
    }

    private static function isSensitive(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SENSITIVE as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }
}
