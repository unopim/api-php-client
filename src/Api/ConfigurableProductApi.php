<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class ConfigurableProductApi extends AbstractApi
{
    /**
     * List all ConfigurableProductApi items (auto-paginated).
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 100): array
    {
        return $this->fetchAll('/api/v1/rest/configrable-products', $limit);
    }

    /**
     * Stream configurable products one by one without loading the full catalog into memory.
     *
     * Example:
     *   foreach ($client->configurableProducts()->cursor() as $product) {
     *       // process $product
     *   }
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function cursor(int $limit = 100): \Generator
    {
        yield from parent::cursor('/api/v1/rest/configrable-products', $limit);
    }

    /**
     * Get a single item by sku.
     *
     * @return array<string, mixed>
     */
    public function get(string $sku): array
    {
        return parent::get('/api/v1/rest/configrable-products/' . urlencode($sku));
    }

    /**
     * Create a new item.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->post('/api/v1/rest/configrable-products', $data);
    }

    /**
     * Update an existing item by sku.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $sku, array $data): array
    {
        return $this->put('/api/v1/rest/configrable-products/' . urlencode($sku), $data);
    }
}
