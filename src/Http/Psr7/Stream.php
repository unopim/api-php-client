<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http\Psr7;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Minimal PSR-7 StreamInterface implementation backed by an in-memory string.
 */
final class Stream implements StreamInterface
{
    /** @var resource|null */
    private $handle;

    private bool $readable;

    private bool $writable;

    private bool $seekable;

    public function __construct(string $content = '')
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open php://temp stream.');
        }

        $this->handle = $handle;
        fwrite($this->handle, $content);
        rewind($this->handle);

        $this->readable = true;
        $this->writable = true;
        $this->seekable = true;
    }

    public function __toString(): string
    {
        if ($this->handle === null) {
            return '';
        }

        try {
            $this->seek(0);

            return (string) stream_get_contents($this->handle);
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function detach(): mixed
    {
        $handle = $this->handle;
        $this->handle = null;

        return $handle;
    }

    public function getSize(): ?int
    {
        if ($this->handle === null) {
            return null;
        }

        $stat = fstat($this->handle);

        return $stat !== false ? $stat['size'] : null;
    }

    public function tell(): int
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }

        $position = ftell($this->handle);

        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return $this->handle === null || feof($this->handle);
    }

    public function isSeekable(): bool
    {
        return $this->seekable && $this->handle !== null;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->seekable) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->handle, $offset, $whence) !== 0) {
            throw new RuntimeException('Unable to seek in stream.');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable && $this->handle !== null;
    }

    public function write(string $string): int
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->writable) {
            throw new RuntimeException('Stream is not writable.');
        }

        $bytes = fwrite($this->handle, $string);

        if ($bytes === false) {
            throw new RuntimeException('Unable to write to stream.');
        }

        return $bytes;
    }

    public function isReadable(): bool
    {
        return $this->readable && $this->handle !== null;
    }

    public function read(int $length): string
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }

        if (! $this->readable) {
            throw new RuntimeException('Stream is not readable.');
        }

        $data = fread($this->handle, $length);

        if ($data === false) {
            throw new RuntimeException('Unable to read from stream.');
        }

        return $data;
    }

    public function getContents(): string
    {
        if ($this->handle === null) {
            throw new RuntimeException('Stream is detached.');
        }

        $contents = stream_get_contents($this->handle);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents.');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        if ($this->handle === null) {
            return $key !== null ? null : [];
        }

        $meta = stream_get_meta_data($this->handle);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
