<?php

namespace App\Exceptions\CommerceV2;

use RuntimeException;
use Throwable;

class CommerceV2ClientException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 503,
        public readonly string $errorCode = 'commerce_v2_unavailable',
        public readonly array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
