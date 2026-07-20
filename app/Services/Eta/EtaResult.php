<?php

namespace App\Services\Eta;

/**
 * Outcome of an ETA submission attempt. `accepted` reflects whether ETA took
 * the document (submitted/valid); `uuid`/`longId` are ETA's identifiers.
 */
final readonly class EtaResult
{
    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $errors
     */
    public function __construct(
        public bool $accepted,
        public string $status,
        public ?string $uuid = null,
        public ?string $longId = null,
        public array $errors = [],
        public array $raw = [],
    ) {}

    public static function accepted(string $uuid, ?string $longId = null, array $raw = []): self
    {
        return new self(true, 'submitted', $uuid, $longId, [], $raw);
    }

    public static function rejected(array $errors, array $raw = []): self
    {
        return new self(false, 'rejected', null, null, $errors, $raw);
    }
}
