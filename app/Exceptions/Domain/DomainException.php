<?php

namespace App\Exceptions\Domain;

use RuntimeException;

class DomainException extends RuntimeException
{
    public function __construct(
        public readonly string $messageKey,
        /** @var array<string, string> */
        public readonly array $replace = [],
        public readonly int $httpStatus = 422,
    ) {
        parent::__construct(trans($messageKey, $replace, app()->getLocale()));
    }
}
