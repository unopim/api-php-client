<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http\Psr7;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * PSR-17 RequestFactoryInterface implementation using the built-in PSR-7 classes.
 */
final class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, mixed $uri): RequestInterface
    {
        return new Request(strtoupper($method), $uri);
    }
}
