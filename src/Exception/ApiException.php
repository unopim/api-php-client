<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Exception;

class ApiException extends UnoPimException
{
    public function __construct(string $message, private readonly int $statusCode = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
