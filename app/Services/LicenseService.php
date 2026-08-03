<?php

namespace App\Services;

use App\Models\InstallationLicense;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    public function install(string $document, string $signature, User $actor): InstallationLicense
    {
        throw_unless($actor->can('licenses.manage'), new AuthorizationException('You cannot install licenses.'));
        $payload = $this->verifiedPayload($document, $signature);

        return DB::transaction(function () use ($payload, $document, $signature, $actor): InstallationLicense {
            $license = InstallationLicense::query()->updateOrCreate(
                ['license_id' => $payload['license_id']],
                [
                    'licensee' => $payload['licensee'],
                    'installation_id' => $payload['installation_id'] ?? null,
                    'edition' => $payload['edition'],
                    'max_users' => $payload['max_users'] ?? null,
                    'features' => $payload['features'] ?? [],
                    'valid_from' => $payload['valid_from'],
                    'expires_at' => $payload['expires_at'],
                    'status' => $this->dateStatus($payload['valid_from'], $payload['expires_at']),
                    'raw_document' => $document,
                    'signature' => $signature,
                    'document_hash' => hash('sha256', $document),
                    'last_verified_at' => now(),
                    'installed_by' => $actor->id,
                ],
            );

            InstallationLicense::query()->whereKeyNot($license->id)->where('status', 'active')->update(['status' => 'superseded']);

            return $license;
        });
    }

    public function verify(InstallationLicense $license): InstallationLicense
    {
        $payload = $this->verifiedPayload($license->raw_document, $license->signature);
        throw_unless(hash_equals($license->document_hash, hash('sha256', $license->raw_document)), new \DomainException(
            'The stored license document has changed.',
        ));

        $license->update([
            'status' => $this->dateStatus($payload['valid_from'], $payload['expires_at']),
            'last_verified_at' => now(),
        ]);

        return $license->fresh();
    }

    public function current(): ?InstallationLicense
    {
        return InstallationLicense::query()->whereIn('status', ['active', 'not_yet_valid', 'expired'])->latest('id')->first();
    }

    public function assertValid(): InstallationLicense
    {
        $license = $this->current();
        throw_if($license === null, new \DomainException('No installation license is installed.'));
        $license = $this->verify($license);
        throw_unless($license->status === 'active', new \DomainException('The installation license is not active.'));

        if ($license->max_users !== null) {
            $activeUsers = User::query()->where('is_active', true)->count();
            throw_if($activeUsers > $license->max_users, new \DomainException('The active user count exceeds the licensed limit.'));
        }

        return $license;
    }

    /** @return array<string, mixed> */
    private function verifiedPayload(string $document, string $signature): array
    {
        $publicKey = str_replace('\\n', "\n", (string) config('jawla.license.public_key'));
        throw_if($publicKey === '', new \DomainException('The vendor license public key is not configured.'));
        $decodedSignature = base64_decode(trim($signature), true);
        throw_if($decodedSignature === false, new \DomainException('The license signature is not valid base64.'));
        $verified = openssl_verify($document, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
        throw_unless($verified === 1, new \DomainException('The license signature is invalid.'));

        $payload = json_decode($document, true, flags: JSON_THROW_ON_ERROR);
        foreach (['license_id', 'licensee', 'edition', 'valid_from', 'expires_at'] as $field) {
            throw_if(blank($payload[$field] ?? null), new \DomainException("License field {$field} is required."));
        }
        throw_unless((bool) preg_match('/^[0-9a-f-]{36}$/i', $payload['license_id']), new \DomainException('License id must be a UUID.'));
        $configuredInstallation = (string) config('jawla.license.installation_id');
        if ($configuredInstallation !== '') {
            throw_unless(hash_equals($configuredInstallation, (string) ($payload['installation_id'] ?? '')), new \DomainException(
                'This license belongs to a different installation.',
            ));
        }

        return $payload;
    }

    private function dateStatus(string $validFrom, string $expiresAt): string
    {
        $today = CarbonImmutable::today();
        if ($today->lt(CarbonImmutable::parse($validFrom)->startOfDay())) {
            return 'not_yet_valid';
        }

        return $today->gt(CarbonImmutable::parse($expiresAt)->endOfDay()) ? 'expired' : 'active';
    }
}
