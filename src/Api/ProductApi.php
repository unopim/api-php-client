<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class ProductApi extends AbstractApi
{
    /**
     * List all ProductApi items (auto-paginated).
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 100): array
    {
        return $this->fetchAll('/api/v1/rest/products', $limit);
    }

    /**
     * Stream products one by one without loading the full catalog into memory.
     *
     * Example:
     *   foreach ($client->products()->cursor() as $product) {
     *       // process $product
     *   }
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function iterate(int $limit = 100): \Generator
    {
        yield from $this->cursor('/api/v1/rest/products', $limit);
    }

    /**
     * Get a single item by sku.
     *
     * @return array<string, mixed>
     */
    public function get(string $sku): array
    {
        return parent::get('/api/v1/rest/products/' . urlencode($sku));
    }

    /**
     * Create a new item.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->post('/api/v1/rest/products', $data);
    }

    /**
     * Update an existing item by sku.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $sku, array $data): array
    {
        return $this->put('/api/v1/rest/products/' . urlencode($sku), $data);
    }

    /**
     * Partially update a product by sku.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function patch(string $sku, array $data): array
    {
        return parent::patch('/api/v1/rest/products/' . urlencode($sku), $data);
    }

    /**
     * Delete a product by sku.
     *
     * @return array<string, mixed>
     */
    public function delete(string $sku): array
    {
        return parent::delete('/api/v1/rest/products/' . urlencode($sku));
    }
}
