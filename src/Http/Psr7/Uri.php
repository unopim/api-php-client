<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Minimal PSR-7 UriInterface implementation.
 */
final class Uri implements UriInterface
{
    private string $scheme;

    private string $userInfo;

    private string $host;

    private ?int $port;

    private string $path;

    private string $query;

    private string $fragment;

    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            $this->scheme   = '';
            $this->userInfo = '';
            $this->host     = '';
            $this->port     = null;
            $this->path     = '';
            $this->query    = '';
            $this->fragment = '';

            return;
        }

        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException(sprintf('Unable to parse URI: %s', $uri));
        }

        $this->scheme   = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $this->host     = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port     = isset($parts['port']) ? (int) $parts['port'] : null;
        $this->path     = $parts['path'] ?? '';
        $this->query    = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';

        $userInfo = $parts['user'] ?? '';

        if (isset($parts['pass'])) {
            $userInfo .= ':' . $parts['pass'];
        }

        $this->userInfo = $userInfo;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null && ! $this->isDefaultPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->isDefaultPort() ? null : $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): static
    {
        $clone         = clone $this;
        $clone->scheme = strtolower($scheme);

        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): static
    {
        $clone           = clone $this;
        $clone->userInfo = $password !== null ? $user . ':' . $password : $user;

        return $clone;
    }

    public function withHost(string $host): static
    {
        $clone       = clone $this;
        $clone->host = strtolower($host);

        return $clone;
    }

    public function withPort(?int $port): static
    {
        $clone       = clone $this;
        $clone->port = $port;

        return $clone;
    }

    public function withPath(string $path): static
    {
        $clone       = clone $this;
        $clone->path = $path;

        return $clone;
    }

    public function withQuery(string $query): static
    {
        $clone        = clone $this;
        $clone->query = ltrim($query, '?');

        return $clone;
    }

    public function withFragment(string $fragment): static
    {
        $clone           = clone $this;
        $clone->fragment = ltrim($fragment, '#');

        return $clone;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();

        if ($authority !== '' || $this->scheme === 'file') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;

        if ($authority !== '' && $path !== '' && ! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $uri .= $path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    private function isDefaultPort(): bool
    {
        $defaults = ['http' => 80, 'https' => 443];

        return isset($defaults[$this->scheme]) && $this->port === $defaults[$this->scheme];
    }
}
