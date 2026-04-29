<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Auth;

/**
 * In-memory OAuth token cache — valid for the lifetime of a PHP request.
 */
class TokenStore
{
    private ?string $accessToken = null;

    private int $expiresAt = 0;

    public function isValid(): bool
    {
        return $this->accessToken !== null && time() < $this->expiresAt - 60;
    }

    public function store(string $token, int $expiresIn): void
    {
        $this->accessToken = $token;
        $this->expiresAt   = time() + $expiresIn;
    }

    public function get(): ?string
    {
        return $this->isValid() ? $this->accessToken : null;
    }

    public function clear(): void
    {
        $this->accessToken = null;
        $this->expiresAt   = 0;
    }
}
