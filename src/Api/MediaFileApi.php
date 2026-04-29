<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Api;

class MediaFileApi extends AbstractApi
{
    /**
     * Upload a product media file and return the server response.
     *
     * @return array<string, mixed>
     */
    public function uploadProductMedia(string $filePath): array
    {
        return $this->client->uploadFile('/api/v1/rest/media-files/product', $filePath);
    }

    /**
     * Upload a category media file and return the server response.
     *
     * @return array<string, mixed>
     */
    public function uploadCategoryMedia(string $filePath): array
    {
        return $this->client->uploadFile('/api/v1/rest/media-files/category', $filePath);
    }
}
