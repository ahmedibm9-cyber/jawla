<?php

namespace Tests\Feature;

use App\Services\Eta\Contracts\EtaClient;
use App\Services\Eta\HttpEtaClient;
use App\Services\Eta\NullEtaClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * B1 transport tests for HttpEtaClient — the OAuth token flow, document
 * submission, and response mapping, exercised with faked HTTP so no real ETA
 * credentials are needed. Real go-live still requires a cert-based EtaSigner +
 * PREPROD validation; these prove the transport/mapping logic is correct.
 */
class HttpEtaClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // avoid a cached token leaking between tests
        config([
            'eta.id_base_url' => 'https://id.preprod.eta.test',
            'eta.api_base_url' => 'https://api.preprod.eta.test',
            'eta.client_id' => 'cid',
            'eta.client_secret' => 'secret',
        ]);
    }

    private function client(): HttpEtaClient
    {
        return app(HttpEtaClient::class);
    }

    public function test_submits_and_maps_acceptance(): void
    {
        Http::fake([
            '*/connect/token' => Http::response(['access_token' => 'tok-123', 'expires_in' => 3600]),
            '*/api/v1/documentsubmissions' => Http::response([
                'submissionId' => 'sub-1',
                'acceptedDocuments' => [['uuid' => 'UUID-1', 'longId' => 'LONG-1', 'internalId' => 'INV-1']],
                'rejectedDocuments' => [],
            ], 200),
        ]);

        $result = $this->client()->submit(['internalID' => 'INV-1']);

        $this->assertTrue($result->accepted);
        $this->assertSame('submitted', $result->status);
        $this->assertSame('UUID-1', $result->uuid);
        $this->assertSame('LONG-1', $result->longId);

        // Auth token was requested and the submission carried the bearer token.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/connect/token')
            && $req['grant_type'] === 'client_credentials');
        Http::assertSent(fn ($req) => str_contains($req->url(), '/documentsubmissions')
            && $req->hasHeader('Authorization', 'Bearer tok-123')
            && $req['documents'][0]['internalID'] === 'INV-1');
    }

    public function test_maps_rejection_with_error_details(): void
    {
        Http::fake([
            '*/connect/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/api/v1/documentsubmissions' => Http::response([
                'acceptedDocuments' => [],
                'rejectedDocuments' => [[
                    'internalId' => 'INV-1',
                    'error' => ['message' => 'Invalid tax id', 'details' => [['message' => 'issuer.id malformed']]],
                ]],
            ], 200),
        ]);

        $result = $this->client()->submit(['internalID' => 'INV-1']);

        $this->assertFalse($result->accepted);
        $this->assertSame('rejected', $result->status);
        $this->assertContains('Invalid tax id', $result->errors);
        $this->assertContains('issuer.id malformed', $result->errors);
    }

    public function test_http_error_is_a_rejection_not_an_exception(): void
    {
        Http::fake([
            '*/connect/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/api/v1/documentsubmissions' => Http::response(['error' => 'server'], 500),
        ]);

        $result = $this->client()->submit(['internalID' => 'INV-1']);

        $this->assertFalse($result->accepted);
        $this->assertNotEmpty($result->errors);
    }

    public function test_auth_failure_is_a_rejection(): void
    {
        Http::fake([
            '*/connect/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $result = $this->client()->submit(['internalID' => 'INV-1']);

        $this->assertFalse($result->accepted);
        $this->assertNotEmpty($result->errors);
    }

    public function test_binding_is_http_client_when_enabled_else_null(): void
    {
        config(['eta.enabled' => false]);
        $this->assertInstanceOf(NullEtaClient::class, app(EtaClient::class));

        config([
            'eta.enabled' => true,
            'eta.api_base_url' => 'https://api.preprod.eta.test',
            'eta.id_base_url' => 'https://id.preprod.eta.test',
        ]);
        $this->assertInstanceOf(HttpEtaClient::class, app(EtaClient::class));
    }
}
