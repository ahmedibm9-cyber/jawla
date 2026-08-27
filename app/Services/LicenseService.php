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
                $this->persistedClaims($payload) + [
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

        $license->update($this->persistedClaims($payload) + [
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

        $activeUsers = User::query()->where('is_active', true)->count();
        throw_if($activeUsers > $license->max_users, new \DomainException('The active user count exceeds the licensed limit.'));

        return $license;
    }

    public function assertRuntimeValid(): ?InstallationLicense
    {
        if (config('jawla.is_demo')) {
            return null;
        }

        return $this->assertValid();
    }

    public function assertRuntimeFeature(string $feature): void
    {
        $license = $this->assertRuntimeValid();
        if ($license === null) {
            return;
        }

        throw_unless(in_array($feature, $license->features ?? [], true), new \DomainException(
            "The installed license does not enable {$feature}.",
        ));
    }

    public function runtimeFeatureEnabled(string $feature): bool
    {
        $license = $this->assertRuntimeValid();

        return $license === null || in_array($feature, $license->features ?? [], true);
    }

    public function assertCanActivateUser(?int $userId = null): void
    {
        if (config('jawla.is_demo') || User::query()->count() === 0) {
            return;
        }

        $license = $this->assertValid();
        $activeUsers = User::query()->where('is_active', true)
            ->when($userId !== null, fn ($query) => $query->whereKeyNot($userId))
            ->count();
        throw_if($activeUsers >= $license->max_users, new \DomainException('The licensed active-user limit has been reached.'));
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
        throw_unless(is_array($payload), new \DomainException('The license document must contain a JSON object.'));
        foreach (['license_id', 'licensee', 'edition', 'valid_from', 'expires_at'] as $field) {
            throw_if(blank($payload[$field] ?? null), new \DomainException("License field {$field} is required."));
        }
        throw_unless((bool) preg_match('/^[0-9a-f-]{36}$/i', $payload['license_id']), new \DomainException('License id must be a UUID.'));
        throw_unless(is_string($payload['licensee']) && mb_strlen($payload['licensee']) <= 255, new \DomainException('Licensee must be a string of at most 255 characters.'));
        throw_unless(is_string($payload['edition']) && preg_match('/^[a-z][a-z0-9_-]{1,49}$/', $payload['edition']) === 1, new \DomainException('License edition is invalid.'));
        throw_unless(! isset($payload['max_users']) || (is_int($payload['max_users']) && $payload['max_users'] > 0), new \DomainException('License max_users must be a positive integer.'));
        throw_unless(! isset($payload['features']) || (is_array($payload['features']) && collect($payload['features'])->every(fn ($feature): bool => is_string($feature) && $feature !== '')), new \DomainException('License features must be non-empty strings.'));
        throw_unless(is_string($payload['valid_from']) && is_string($payload['expires_at']), new \DomainException('License validity dates must be strings.'));
        try {
            $validFrom = CarbonImmutable::createFromFormat('!Y-m-d', $payload['valid_from']);
            $expiresAt = CarbonImmutable::createFromFormat('!Y-m-d', $payload['expires_at']);
        } catch (\Throwable) {
            throw new \DomainException('License validity dates are invalid.');
        }
        throw_unless($validFrom !== null && $expiresAt !== null && $expiresAt->gte($validFrom), new \DomainException('License validity dates are invalid.'));
        $configuredInstallation = (string) config('jawla.license.installation_id');
        if (app()->isProduction()) {
            throw_if($configuredInstallation === '', new \DomainException('JAWLA_INSTALLATION_ID is required in production.'));
        }
        if ($configuredInstallation !== '') {
            throw_unless(hash_equals($configuredInstallation, (string) ($payload['installation_id'] ?? '')), new \DomainException(
                'This license belongs to a different installation.',
            ));
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{licensee: string, installation_id: string|null, edition: string, max_users: int|null, features: list<string>, valid_from: string, expires_at: string}
     */
    private function persistedClaims(array $payload): array
    {
        return [
            'licensee' => $payload['licensee'],
            'installation_id' => $payload['installation_id'] ?? null,
            'edition' => $payload['edition'],
            'max_users' => $payload['max_users'] ?? null,
            'features' => array_values(array_unique($payload['features'] ?? [])),
            'valid_from' => $payload['valid_from'],
            'expires_at' => $payload['expires_at'],
        ];
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
