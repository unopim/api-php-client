<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class CategoryApi extends AbstractApi
{
    /**
     * List all CategoryApi items (auto-paginated).
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 100): array
    {
        return $this->fetchAll('/api/v1/rest/categories', $limit);
    }

    /**
     * Get a single item by code.
     *
     * @return array<string, mixed>
     */
    public function get(string $code): array
    {
        return parent::get('/api/v1/rest/categories/' . urlencode($code));
    }
    /**
     * Create a new item.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->post('/api/v1/rest/categories', $data);
    }
    /**
     * Update an existing item by code.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $code, array $data): array
    {
        return $this->put('/api/v1/rest/categories/' . urlencode($code), $data);
    }
}
