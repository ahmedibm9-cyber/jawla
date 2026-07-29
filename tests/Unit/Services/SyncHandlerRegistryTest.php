<?php

namespace Tests\Unit\Services;

use App\Services\Sync\Contracts\SyncHandler;
use App\Services\Sync\SyncHandlerRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncHandlerRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeRegistry(): SyncHandlerRegistry
    {
        $container = Mockery::mock(Container::class);
        $container->shouldReceive('tagged')->with('sync.handler')->andReturn(collect());

        return new SyncHandlerRegistry($container);
    }

    public function test_register_and_has(): void
    {
        $registry = $this->makeRegistry();

        $handler = Mockery::mock(SyncHandler::class);
        $handler->shouldReceive('type')->andReturn('sale');

        $registry->register('sale', $handler);

        $this->assertTrue($registry->has('sale'));
        $this->assertFalse($registry->has('return'));
    }

    public function test_get_returns_registered_handler(): void
    {
        $registry = $this->makeRegistry();

        $handler = Mockery::mock(SyncHandler::class);
        $handler->shouldReceive('type')->andReturn('sale');

        $registry->register('sale', $handler);

        $this->assertSame($handler, $registry->get('sale'));
        $this->assertNull($registry->get('nonexistent'));
    }

    public function test_types_returns_registered_keys(): void
    {
        $registry = $this->makeRegistry();

        $handler1 = Mockery::mock(SyncHandler::class);
        $handler1->shouldReceive('type')->andReturn('sale');

        $handler2 = Mockery::mock(SyncHandler::class);
        $handler2->shouldReceive('type')->andReturn('return');

        $registry->register('sale', $handler1);
        $registry->register('return', $handler2);

        $types = $registry->types();

        $this->assertContains('sale', $types);
        $this->assertContains('return', $types);
    }

    public function test_discover_from_tagged_handlers(): void
    {
        $handler = Mockery::mock(SyncHandler::class);
        $handler->shouldReceive('type')->andReturn('sale');

        $container = Mockery::mock(Container::class);
        $container->shouldReceive('tagged')->with('sync.handler')->andReturn(collect([$handler]));

        $registry = new SyncHandlerRegistry($container);

        $this->assertTrue($registry->has('sale'));
        $this->assertSame($handler, $registry->get('sale'));
    }
}
