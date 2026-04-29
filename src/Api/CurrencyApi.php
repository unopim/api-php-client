<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class CurrencyApi extends AbstractApi
{
    /**
     * List all CurrencyApi items (auto-paginated).
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit = 100): array
    {
        return $this->fetchAll('/api/v1/rest/currencies', $limit);
    }

    /**
     * Get a single item by code.
     *
     * @return array<string, mixed>
     */
    public function get(string $code): array
    {
        return parent::get('/api/v1/rest/currencies/' . urlencode($code));
    }
}
