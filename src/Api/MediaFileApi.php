<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class MediaFileApi extends AbstractApi
{
    /**
     * Upload a product media file alongside the required UnoPim form fields.
     *
     * @param string $filePath
     * @param string $sku
     * @param string $attribute
     * @return array<string, mixed>
     */
    public function uploadProductMedia(string $filePath, string $sku = '', string $attribute = ''): array
    {
        $fields = array_filter([
            'sku'       => $sku,
            'attribute' => $attribute,
        ], static fn ($v) => $v !== '');

        return $this->client->uploadFile('/api/v1/rest/media-files/product', $filePath, $fields);
    }

    /**
     * Upload a category media file alongside the required UnoPim form fields.
     *
     * @param string $filePath
     * @param string $code
     * @param string $attribute
     * @return array<string, mixed>
     */
    public function uploadCategoryMedia(string $filePath, string $code = '', string $attribute = ''): array
    {
        $fields = array_filter([
            'code'      => $code,
            'attribute' => $attribute,
        ], static fn ($v) => $v !== '');

        return $this->client->uploadFile('/api/v1/rest/media-files/category', $filePath, $fields);
    }
}
