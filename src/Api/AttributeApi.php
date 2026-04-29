<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class AttributeApi extends AbstractApi
{
    /**
     * List all AttributeApi items (auto-paginated).
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 100): array
    {
        return $this->fetchAll('/api/v1/rest/attributes', $limit);
    }

    /**
     * Get a single item by code.
     *
     * @return array<string, mixed>
     */
    public function get(string $code): array
    {
        return parent::get('/api/v1/rest/attributes/' . urlencode($code));
    }
    /**
     * Create a new item.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->post('/api/v1/rest/attributes', $data);
    }
    /**
     * Update an existing item by code.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $code, array $data): array
    {
        return $this->put('/api/v1/rest/attributes/' . urlencode($code), $data);
    }
    /**
     * List options for a select/multiselect attribute.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listOptions(string $attributeCode, int $limit = 100): array
    {
        return $this->fetchAll('/api/v1/rest/attributes/' . urlencode($attributeCode) . '/options', $limit);
    }

    /**
     * Create options for an attribute.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createOptions(string $attributeCode, array $data): array
    {
        return $this->post('/api/v1/rest/attributes/' . urlencode($attributeCode) . '/options', $data);
    }

    /**
     * Update options for an attribute.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateOptions(string $attributeCode, array $data): array
    {
        return $this->put('/api/v1/rest/attributes/' . urlencode($attributeCode) . '/options', $data);
    }
}