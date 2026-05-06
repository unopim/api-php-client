<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

use Unopim\ApiClient\UnoPimClient;

abstract class AbstractApi
{
    public function __construct(protected readonly UnoPimClient $client)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchAll(string $endpoint, int $limit = 100): array
    {
        return $this->client->fetchAll($endpoint, $limit);
    }

    /**
     * Yield items one by one from a paginated endpoint — memory-efficient
     * alternative to fetchAll() for large catalogs.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    protected function cursor(string $endpoint, int $limit = 100): \Generator
    {
        yield from $this->client->cursor($endpoint, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    protected function get(string $endpoint): array
    {
        return $this->client->get($endpoint);
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function post(string $endpoint, array $payload): array
    {
        return $this->client->post($endpoint, $payload);
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function put(string $endpoint, array $payload): array
    {
        return $this->client->put($endpoint, $payload);
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function patch(string $endpoint, array $payload): array
    {
        return $this->client->patch($endpoint, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    protected function delete(string $endpoint): array
    {
        return $this->client->delete($endpoint);
    }
}
