<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Cache;

/**
 * Simple in-memory TTL cache for read-only API responses.
 *
 * Designed for endpoints that change rarely (locales, currencies, channels).
 * The cache lives only for the duration of the PHP process / request.
 */
final class ResponseCache
{
    /** @var array<string, array{data: array<mixed>, expires_at: int}> */
    private array $store = [];

    /**
     * Retrieve a cached value, or null if missing / expired.
     *
     * @return array<mixed>|null
     */
    public function get(string $key): ?array
    {
        if (! $this->has($key)) {
            return null;
        }

        return $this->store[$key]['data'];
    }

    /**
     * Store a value with an optional TTL (seconds). Default 300 s (5 minutes).
     *
     * @param array<mixed> $data
     */
    public function set(string $key, array $data, int $ttlSeconds = 300): void
    {
        $this->store[$key] = [
            'data'       => $data,
            'expires_at' => time() + max(1, $ttlSeconds),
        ];
    }

    /**
     * Return true when the key exists and has not expired.
     */
    public function has(string $key): bool
    {
        if (! isset($this->store[$key])) {
            return false;
        }

        if (time() >= $this->store[$key]['expires_at']) {
            unset($this->store[$key]);

            return false;
        }

        return true;
    }

    /**
     * Evict all cached entries.
     */
    public function clear(): void
    {
        $this->store = [];
    }
}
