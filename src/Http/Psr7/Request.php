<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http\Psr7;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Minimal PSR-7 RequestInterface implementation.
 */
final class Request implements RequestInterface
{
    use MessageTrait;

    private string $requestTarget = '';

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        private string $method,
        private UriInterface|string $uri,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1'
    ) {
        if (is_string($this->uri)) {
            $this->uri = new Uri($this->uri);
        }

        $this->setHeaders($headers);
        $this->protocolVersion = $protocolVersion;

        if ($body === null) {
            $this->body = new Stream('');
        } elseif (is_string($body)) {
            $this->body = new Stream($body);
        } else {
            $this->body = $body;
        }

        // Automatically set the Host header from the URI if not provided
        if (! $this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $host = $this->uri->getHost();

            if ($this->uri->getPort() !== null) {
                $host .= ':' . $this->uri->getPort();
            }

            $this->headers['Host']     = [$host];
            $this->headerNames['host'] = 'Host';
        }
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== '') {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        $clone                = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone         = clone $this;
        $clone->method = strtoupper($method);

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone      = clone $this;
        $clone->uri = $uri;

        if (! $preserveHost && $uri->getHost() !== '') {
            $host = $uri->getHost();

            if ($uri->getPort() !== null) {
                $host .= ':' . $uri->getPort();
            }

            $clone->headers['Host']     = [$host];
            $clone->headerNames['host'] = 'Host';
        }

        return $clone;
    }
}
