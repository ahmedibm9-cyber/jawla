<?php

namespace Tests\Feature\SystemPage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_200_with_ok_status(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson([
                'status' => 'ok',
                'db' => 'ok',
                'cache' => 'ok',
            ]);
    }

    public function test_health_returns_503_when_db_is_down(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $response = $this->get('/health');

        $response->assertStatus(503)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson([
                'status' => 'degraded',
                'db' => 'failed',
            ]);
    }
}
