<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http;

/**
 * @deprecated Use CurlClient (PSR-18 ClientInterface) instead.
 *
 * This class is retained for backwards compatibility only and will be removed
 * in the next major version. Migrate to the PSR-18-based CurlClient:
 *
 *   $client = UnoPimClient::create($baseUrl, $clientId, $clientSecret);
 *   // or inject your own PSR-18 client via:
 *   $client = UnoPimClient::createWithHttpClient(...);
 */
class CurlHttpClient
{
}
