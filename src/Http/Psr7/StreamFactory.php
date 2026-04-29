<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http\Psr7;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * PSR-17 StreamFactoryInterface implementation using the built-in PSR-7 Stream.
 */
final class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $handle = fopen($filename, $mode);

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open file: %s', $filename));
        }

        return $this->createStreamFromResource($handle);
    }

    public function createStreamFromResource(mixed $resource): StreamInterface
    {
        if (! is_resource($resource)) {
            throw new RuntimeException('Argument must be a valid resource.');
        }

        $stream = new Stream('');
        // Replace the internal handle by detaching the empty stream and storing the resource directly.
        // We use a workaround: read all content and wrap it.
        $content = stream_get_contents($resource);

        if ($content === false) {
            throw new RuntimeException('Unable to read from resource.');
        }

        return new Stream($content);
    }
}
