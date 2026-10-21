<?php

namespace App\Exceptions\Api;

use DomainException;

class IdempotencyConflictException extends DomainException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
