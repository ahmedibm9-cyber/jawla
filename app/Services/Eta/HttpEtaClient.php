<?php

namespace App\Services\Eta;

use App\Services\Eta\Contracts\EtaClient;
use App\Services\Eta\Contracts\EtaSigner;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Real transport for ETA e-invoice submission (Egyptian Tax Authority).
 *
 * Flow (client-credentials, per the ETA e-invoicing API):
 *   1. OAuth token   → POST {id_base_url}/connect/token (client_credentials, scope InvoicingAPI)
 *   2. Sign document → EtaSigner (CAdES-BES with the taxpayer certificate)
 *   3. Submit        → POST {api_base_url}/api/v1/documentsubmissions
 *   4. Map response  → EtaResult (accepted uuid/longId | rejected errors)
 *
 * Bound only when ETA is enabled (else NullEtaClient stays). The OAuth transport
 * + submission + response mapping are fully built and unit-tested with faked
 * HTTP. Two things still gate real go-live and are intentionally NOT faked away:
 *   - a real EtaSigner (needs the taxpayer signing certificate), and
 *   - validation of the produced document + exact endpoints against the official
 *     ETA SDK on the PREPROD environment.
 * Until the signer is provisioned the document is submitted unsigned, which ETA
 * preprod rejects — surfaced honestly as a rejection, never a false success.
 */
class HttpEtaClient implements EtaClient
{
    private const TOKEN_CACHE_KEY = 'eta.access_token';

    public function __construct(private readonly EtaSigner $signer) {}

    public function submit(array $document): EtaResult
    {
        try {
            $token = $this->accessToken();
        } catch (Throwable $e) {
            return EtaResult::rejected(['ETA authentication failed: '.$e->getMessage()]);
        }

        $signed = $this->signer->sign($document);

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('eta.timeout', 30))
                ->post($this->apiUrl('/api/v1/documentsubmissions'), [
                    'documents' => [$signed],
                ]);
        } catch (Throwable $e) {
            return EtaResult::rejected(['ETA submission request failed: '.$e->getMessage()]);
        }

        if (! $response->successful()) {
            return EtaResult::rejected(
                ['ETA returned HTTP '.$response->status()],
                $this->safeJson($response),
            );
        }

        return $this->mapResult($this->safeJson($response));
    }

    /** Acquire (and cache) an OAuth access token via client-credentials. */
    private function accessToken(): string
    {
        return cache()->remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('eta.timeout', 30))
                ->post($this->idUrl('/connect/token'), [
                    'grant_type' => 'client_credentials',
                    'client_id' => (string) config('eta.client_id'),
                    'client_secret' => (string) config('eta.client_secret'),
                    'scope' => 'InvoicingAPI',
                ]);

            $token = (string) ($response->json('access_token') ?? '');
            throw_if($token === '', new \RuntimeException('no access_token in ETA token response'));

            return $token;
        });
    }

    /**
     * Map ETA's documentsubmissions response to an EtaResult. ETA returns
     * acceptedDocuments (with uuid/longId) and rejectedDocuments (with errors).
     *
     * @param  array<string, mixed>  $body
     */
    private function mapResult(array $body): EtaResult
    {
        $accepted = $body['acceptedDocuments'] ?? [];
        if (! empty($accepted)) {
            $first = $accepted[0];

            return EtaResult::accepted(
                (string) ($first['uuid'] ?? ''),
                isset($first['longId']) ? (string) $first['longId'] : null,
                $body,
            );
        }

        $rejected = $body['rejectedDocuments'] ?? [];
        $errors = [];
        foreach ($rejected as $doc) {
            $error = $doc['error'] ?? [];
            $errors[] = (string) ($error['message'] ?? $error['code'] ?? 'ETA rejected the document');
            foreach ($error['details'] ?? [] as $detail) {
                $errors[] = (string) ($detail['message'] ?? $detail['propertyPath'] ?? '');
            }
        }

        return EtaResult::rejected(
            $errors !== [] ? array_values(array_filter($errors)) : ['ETA accepted no documents'],
            $body,
        );
    }

    /** @return array<string, mixed> */
    private function safeJson($response): array
    {
        try {
            return (array) ($response->json() ?? []);
        } catch (Throwable) {
            return [];
        }
    }

    private function idUrl(string $path): string
    {
        return rtrim((string) config('eta.id_base_url'), '/').$path;
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) config('eta.api_base_url'), '/').$path;
    }
}
