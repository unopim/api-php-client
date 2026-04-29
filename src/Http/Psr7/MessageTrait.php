<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Shared PSR-7 message functionality (headers + body) used by Request and Response.
 */
trait MessageTrait
{
    /** @var array<string, list<string>> */
    private array $headers = [];

    /** @var array<string, string> Map of lowercase header name → original case name */
    private array $headerNames = [];

    private string $protocolVersion = '1.1';

    private StreamInterface $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone                  = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @return list<string>
     */
    public function getHeader(string $name): array
    {
        $lower = strtolower($name);

        if (! isset($this->headerNames[$lower])) {
            return [];
        }

        return $this->headers[$this->headerNames[$lower]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $lower = strtolower($name);

        if (isset($clone->headerNames[$lower])) {
            unset($clone->headers[$clone->headerNames[$lower]]);
        }

        $clone->headerNames[$lower] = $name;
        $clone->headers[$name]      = is_array($value) ? array_values($value) : [$value];

        return $clone;
    }

    public function withAddedHeader(string $name, mixed $value): static
    {
        $clone = clone $this;
        $lower = strtolower($name);

        if (isset($clone->headerNames[$lower])) {
            $existing = $clone->headerNames[$lower];
            $clone->headers[$existing] = array_merge(
                $clone->headers[$existing],
                is_array($value) ? array_values($value) : [$value]
            );
        } else {
            $clone->headerNames[$lower] = $name;
            $clone->headers[$name]      = is_array($value) ? array_values($value) : [$value];
        }

        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $lower = strtolower($name);

        if (isset($clone->headerNames[$lower])) {
            $original = $clone->headerNames[$lower];
            unset($clone->headers[$original], $clone->headerNames[$lower]);
        }

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $clone       = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function setHeaders(array $headers): void
    {
        $this->headers     = [];
        $this->headerNames = [];

        foreach ($headers as $name => $value) {
            $lower                    = strtolower($name);
            $this->headerNames[$lower] = $name;
            $this->headers[$name]     = is_array($value) ? array_values($value) : [$value];
        }
    }
}
