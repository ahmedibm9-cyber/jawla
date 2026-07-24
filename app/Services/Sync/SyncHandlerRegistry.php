<?php

namespace App\Services\Sync;

use App\Services\Sync\Contracts\SyncHandler;
use Illuminate\Contracts\Container\Container;

/**
 * Registry of sync operation handlers keyed by operation type. Handlers are
 * discovered automatically from the container's `sync.handler` tag — adding a
 * new offline operation requires only: (1) create the handler class, (2) tag it
 * in a service provider with `sync.handler`.
 *
 * Unknown types are reported as "unsupported" by the SyncService rather than
 * failing the whole batch.
 */
class SyncHandlerRegistry
{
    /** @var array<string, SyncHandler> */
    private array $handlers = [];

    private bool $discovered = false;

    public function __construct(private readonly Container $container) {}

    /**
     * Register a single handler by type. Called from tests to register a mock
     * without going through the container. Also used as the fallback when
     * tag-based discovery finds nothing.
     */
    public function register(string $type, SyncHandler $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function has(string $type): bool
    {
        $this->discover();

        return isset($this->handlers[$type]);
    }

    public function get(string $type): ?SyncHandler
    {
        $this->discover();

        return $this->handlers[$type] ?? null;
    }

    /** @return list<string> */
    public function types(): array
    {
        $this->discover();

        return array_keys($this->handlers);
    }

    /**
     * Auto-discover handlers tagged with `sync.handler` in the container.
     * Each tagged service must implement SyncHandler and return its type from
     * the `type()` method.
     */
    private function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        /** @var list<SyncHandler> $tagged */
        $tagged = $this->container->tagged('sync.handler');

        foreach ($tagged as $handler) {
            $this->handlers[$handler->type()] = $handler;
        }
    }
}
