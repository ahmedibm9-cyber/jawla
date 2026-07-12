<?php

namespace App\Exceptions\Domain;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    public function __construct(
        public readonly string $messageKey,
        public readonly array $replace = [],
        public readonly int $httpStatus = 422,
    ) {
        parent::__construct(trans($messageKey, $replace, app()->getLocale()));
    }
}