<?php

namespace App\Services\Sync;

use App\Services\Sync\Contracts\SyncHandler;

/**
 * Registry of sync operation handlers keyed by operation type. Production
 * handlers (offline sale, payment, visit, …) are registered in a service
 * provider as they are built; until then unknown types are reported as
 * "unsupported" by the SyncService rather than failing the whole batch.
 */
class SyncHandlerRegistry
{
    /** @var array<string, SyncHandler> */
    private array $handlers = [];

    public function register(string $type, SyncHandler $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function has(string $type): bool
    {
        return isset($this->handlers[$type]);
    }

    public function get(string $type): ?SyncHandler
    {
        return $this->handlers[$type] ?? null;
    }

    /** @return list<string> */
    public function types(): array
    {
        return array_keys($this->handlers);
    }
}
