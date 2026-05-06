<?php

declare(strict_types=1);

namespace Unopim\ApiClient;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Unopim\ApiClient\Api\AttributeApi;
use Unopim\ApiClient\Api\AttributeFamilyApi;
use Unopim\ApiClient\Api\AttributeGroupApi;
use Unopim\ApiClient\Api\CategoryApi;
use Unopim\ApiClient\Api\CategoryFieldApi;
use Unopim\ApiClient\Api\ChannelApi;
use Unopim\ApiClient\Api\ConfigurableProductApi;
use Unopim\ApiClient\Api\CurrencyApi;
use Unopim\ApiClient\Api\LocaleApi;
use Unopim\ApiClient\Api\MediaFileApi;
use Unopim\ApiClient\Api\ProductApi;
use Unopim\ApiClient\Auth\TokenStore;
use Unopim\ApiClient\Cache\ResponseCache;
use Unopim\ApiClient\Exception\ApiException;
use Unopim\ApiClient\Exception\AuthenticationException;
use Unopim\ApiClient\Http\CurlClient;
use Unopim\ApiClient\Http\Psr7\RequestFactory;
use Unopim\ApiClient\Http\Psr7\StreamFactory;

/**
 * Entry point for the UnoPim PHP API Client.
 *
 * ## Zero-config (uses built-in cURL client):
 *   $client = UnoPimClient::create('https://my-unopim.com', 'client_id', 'client_secret');
 *
 * ## With cache TTL:
 *   $client = UnoPimClient::create('https://my-unopim.com', 'id', 'secret', cacheTtl: 600);
 *
 * ## With Guzzle:
 *   $guzzle  = new \GuzzleHttp\Client();
 *   $factory = new \GuzzleHttp\Psr7\HttpFactory();
 *   $client  = UnoPimClient::createWithHttpClient('https://my-unopim.com', 'id', 'secret', $guzzle, $factory, $factory);
 *
 * ## With Symfony HttpClient:
 *   $symfony = \Symfony\Component\HttpClient\Psr18Client::fromEnvironment();
 *   $client  = UnoPimClient::createWithHttpClient('https://my-unopim.com', 'id', 'secret', $symfony, $symfony, $symfony);
 */
final class UnoPimClient
{
    /**
     * Endpoint prefixes whose GET responses are eligible for in-memory caching.
     * These resources change very rarely within a single process lifetime.
     *
     * @var list<string>
     */
    private const CACHEABLE_PREFIXES = [
        '/api/v1/rest/locales',
        '/api/v1/rest/currencies',
        '/api/v1/rest/channels',
    ];

    private readonly TokenStore $tokenStore;

    private readonly ResponseCache $responseCache;

    private int $cacheTtl;

    private function __construct(
        private readonly string $baseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $username,
        private readonly string $password,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        int $cacheTtl = 300,
    ) {
        $this->tokenStore    = new TokenStore();
        $this->responseCache = new ResponseCache();
        $this->cacheTtl      = max(0, $cacheTtl);
    }

    /**
     * Create a client using password grant (required by UnoPim API).
     *
     * @param int $cacheTtl TTL in seconds for cacheable read-only endpoints (0 = disabled)
     */
    public static function create(
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
        int $cacheTtl = 300,
    ): self {
        return new self(
            baseUrl:        rtrim($baseUrl, '/'),
            clientId:       $clientId,
            clientSecret:   $clientSecret,
            username:       $username,
            password:       $password,
            httpClient:     new CurlClient(),
            requestFactory: new RequestFactory(),
            streamFactory:  new StreamFactory(),
            cacheTtl:       $cacheTtl,
        );
    }

    /**
     * Create a client with a custom PSR-18 HTTP client and PSR-17 factories.
     *
     * @param int $cacheTtl TTL in seconds for cacheable read-only endpoints (0 = disabled)
     */
    public static function createWithHttpClient(
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        int $cacheTtl = 300,
    ): self {
        return new self(
            baseUrl:        rtrim($baseUrl, '/'),
            clientId:       $clientId,
            clientSecret:   $clientSecret,
            username:       $username,
            password:       $password,
            httpClient:     $httpClient,
            requestFactory: $requestFactory,
            streamFactory:  $streamFactory,
            cacheTtl:       $cacheTtl,
        );
    }

    // -----------------------------------------------------------------------
    // API resource accessors
    // -----------------------------------------------------------------------

    public function locales(): LocaleApi
    {
        return new LocaleApi($this);
    }

    public function currencies(): CurrencyApi
    {
        return new CurrencyApi($this);
    }

    public function channels(): ChannelApi
    {
        return new ChannelApi($this);
    }

    public function categories(): CategoryApi
    {
        return new CategoryApi($this);
    }

    public function categoryFields(): CategoryFieldApi
    {
        return new CategoryFieldApi($this);
    }

    public function attributes(): AttributeApi
    {
        return new AttributeApi($this);
    }

    public function attributeGroups(): AttributeGroupApi
    {
        return new AttributeGroupApi($this);
    }

    public function attributeFamilies(): AttributeFamilyApi
    {
        return new AttributeFamilyApi($this);
    }

    public function products(): ProductApi
    {
        return new ProductApi($this);
    }

    public function configurableProducts(): ConfigurableProductApi
    {
        return new ConfigurableProductApi($this);
    }

    public function mediaFiles(): MediaFileApi
    {
        return new MediaFileApi($this);
    }

    /**
     * Expose the in-memory response cache so callers can inspect or evict entries.
     *
     * Example: $client->cache()->clear();
     */
    public function cache(): ResponseCache
    {
        return $this->responseCache;
    }

    // -----------------------------------------------------------------------
    // HTTP helpers (used by resource APIs via AbstractApi)
    // -----------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function get(string $endpoint): array
    {
        if ($this->cacheTtl > 0 && $this->isCacheableEndpoint($endpoint)) {
            $cached = $this->responseCache->get($endpoint);

            if ($cached !== null) {
                return $cached;
            }

            $result = $this->request('GET', $endpoint);
            $this->responseCache->set($endpoint, $result, $this->cacheTtl);

            return $result;
        }

        return $this->request('GET', $endpoint);
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $payload): array
    {
        return $this->request('POST', $endpoint, $payload);
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $payload): array
    {
        return $this->request('PUT', $endpoint, $payload);
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function patch(string $endpoint, array $payload): array
    {
        return $this->request('PATCH', $endpoint, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Fetch all pages and return a flat array of items.
     *
     * When the injected HTTP client is the built-in CurlClient, page 2+ are
     * fetched in parallel using curl_multi_* for significantly better throughput
     * on large catalogs. Other PSR-18 clients fall back to sequential fetching.
     *
     * The public signature is unchanged — the method always returns a flat array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $endpoint, int $limit = 100): array
    {
        $sep      = str_contains($endpoint, '?') ? '&' : '?';
        $firstUrl = $endpoint . $sep . "limit={$limit}&page=1";

        $firstResponse = $this->get($firstUrl);
        $firstBatch    = $firstResponse['items'] ?? $firstResponse['data'] ?? [];

        // Determine total count from various possible response shapes
        $total = $this->extractTotalCount($firstResponse);

        // If we got fewer items than the limit, or no total info, we're done
        if (count($firstBatch) < $limit || ($total !== null && $total <= $limit)) {
            return array_values($firstBatch);
        }

        // Calculate remaining pages
        $totalPages = $total !== null
            ? (int) ceil($total / $limit)
            : null; // unknown — we'll stop when a page returns < limit items

        $items = $firstBatch;

        if ($this->httpClient instanceof CurlClient) {
            $items = $this->fetchRemainingParallel($endpoint, $sep, $limit, $firstBatch, $totalPages);
        } else {
            $items = $this->fetchRemainingSequential($endpoint, $sep, $limit, $firstBatch, $totalPages);
        }

        return array_values($items);
    }

    /**
     * Yield items one by one from a paginated endpoint without loading all pages
     * into memory at once. Ideal for processing large catalogs.
     *
     * Example:
     *   foreach ($client->cursor('/api/v1/rest/products') as $product) {
     *       // process $product without ever holding the full list in RAM
     *   }
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function cursor(string $endpoint, int $limit = 100): \Generator
    {
        $page = 1;

        do {
            $sep      = str_contains($endpoint, '?') ? '&' : '?';
            $response = $this->get($endpoint . $sep . "limit={$limit}&page={$page}");
            $fetched  = $response['items'] ?? $response['data'] ?? [];

            foreach ($fetched as $item) {
                yield $item;
            }

            $page++;
        } while (count($fetched) === $limit);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Upload a file using multipart/form-data via a raw cURL call.
     *
     * PSR-7 multipart bodies are intentionally left to the transport layer here
     * because forming a fully standards-compliant multipart stream would require
     * a third-party PSR-7 multipart library. The built-in CurlClient delegates
     * directly to cURL's native CURLFile support, which is the most reliable
     * approach for binary uploads.
     *
     * @param string $endpoint
     * @param string $filePath
     * @param array $fields
     * @return array<string, mixed>
     */
    public function uploadFile(string $endpoint, string $filePath, array $fields = []): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $token = $this->getAccessToken();
        $url   = $this->baseUrl . $endpoint;

        $postFields         = $fields;
        $postFields['file'] = new \CURLFile($filePath);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new ApiException(sprintf('cURL error %d: %s', $errno, $error));
        }

        if ($code >= 400) {
            throw new ApiException(sprintf('Media upload failed HTTP %d: %s', $code, (string) $body), $code);
        }

        $decoded = json_decode((string) $body, true);

        return is_array($decoded) ? $decoded : [];
    }

    // -----------------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------------

    /**
     * @param  array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, ?array $payload = null): array
    {
        $token = $this->getAccessToken();
        $url   = $this->baseUrl . $endpoint;

        $body    = $payload !== null ? json_encode($payload) : '';
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer ' . $token);

        if ($body !== '' && $body !== false) {
            $stream  = $this->streamFactory->createStream($body);
            $request = $request->withBody($stream);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new ApiException('HTTP client error: ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw new ApiException(
                sprintf('UnoPim API error %d on %s %s: %s', $statusCode, $method, $url, (string) $response->getBody()),
                $statusCode,
            );
        }

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function getAccessToken(): string
    {
        if ($this->tokenStore->isValid()) {
            return (string) $this->tokenStore->get();
        }

        // UnoPim requires the password grant with form-encoded body
        $payload = http_build_query([
            'grant_type'    => 'password',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username'      => $this->username,
            'password'      => $this->password,
            'scope'         => '',
        ]);

        $request = $this->requestFactory->createRequest('POST', $this->baseUrl . '/oauth/token')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream($payload));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new AuthenticationException('HTTP client error during authentication: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new AuthenticationException(
                'UnoPim authentication failed: HTTP ' . $response->getStatusCode()
            );
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data) || empty($data['access_token'])) {
            throw new AuthenticationException('UnoPim authentication failed: no access_token returned.');
        }

        $this->tokenStore->store($data['access_token'], (int) ($data['expires_in'] ?? 3600));

        return $data['access_token'];
    }

    /**
     * Try to extract a total item count from a paginated API response.
     * Handles common shapes: top-level `total`, `total_count`, and `meta.total`.
     */
    private function extractTotalCount(mixed $response): ?int
    {
        if (! is_array($response)) {
            return null;
        }

        foreach (['total', 'total_count'] as $key) {
            if (isset($response[$key]) && is_numeric($response[$key])) {
                return (int) $response[$key];
            }
        }

        if (isset($response['meta']['total']) && is_numeric($response['meta']['total'])) {
            return (int) $response['meta']['total'];
        }

        return null;
    }

    /**
     * Fetch pages 2+ in parallel using CurlClient::sendMultiple().
     *
     * @param  array<int, array<string, mixed>> $firstBatch
     * @return array<int, array<string, mixed>>
     */
    private function fetchRemainingParallel(
        string $endpoint,
        string $sep,
        int $limit,
        array $firstBatch,
        ?int $totalPages,
    ): array {
        $items = $firstBatch;

        /** @var CurlClient $curlClient */
        $curlClient = $this->httpClient;
        $token      = $this->getAccessToken();

        if ($totalPages !== null) {
            // We know exactly how many pages to fetch
            $pageNumbers = range(2, $totalPages);
        } else {
            // Unknown total — fetch speculatively in batches until a page returns < limit
            $pageNumbers = null;
        }

        if ($pageNumbers !== null) {
            $requests = [];

            foreach ($pageNumbers as $page) {
                $url        = $this->baseUrl . $endpoint . $sep . "limit={$limit}&page={$page}";
                $requests[] = $this->requestFactory->createRequest('GET', $url)
                    ->withHeader('Accept', 'application/json')
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Authorization', 'Bearer ' . $token);
            }

            $responses = $curlClient->sendMultiple($requests);

            foreach ($responses as $response) {
                $decoded = json_decode((string) $response->getBody(), true);
                $batch   = is_array($decoded) ? ($decoded['items'] ?? $decoded['data'] ?? []) : [];
                $items   = array_merge($items, $batch);
            }
        } else {
            // Speculative parallel fetch: fetch in batches of batchSize until done
            $page = 2;

            do {
                $batchPages = range($page, $page + 9); // 10-page look-ahead
                $requests   = [];

                foreach ($batchPages as $p) {
                    $url        = $this->baseUrl . $endpoint . $sep . "limit={$limit}&page={$p}";
                    $requests[] = $this->requestFactory->createRequest('GET', $url)
                        ->withHeader('Accept', 'application/json')
                        ->withHeader('Content-Type', 'application/json')
                        ->withHeader('Authorization', 'Bearer ' . $token);
                }

                $responses    = $curlClient->sendMultiple($requests);
                $lastBatch    = [];

                foreach ($responses as $response) {
                    $decoded   = json_decode((string) $response->getBody(), true);
                    $batch     = is_array($decoded) ? ($decoded['items'] ?? $decoded['data'] ?? []) : [];
                    $items     = array_merge($items, $batch);
                    $lastBatch = $batch;
                }

                $page += count($batchPages);
            } while (count($lastBatch) === $limit);
        }

        return $items;
    }

    /**
     * Fetch pages 2+ sequentially (fallback for non-CurlClient transports).
     *
     * @param  array<int, array<string, mixed>> $firstBatch
     * @return array<int, array<string, mixed>>
     */
    private function fetchRemainingSequential(
        string $endpoint,
        string $sep,
        int $limit,
        array $firstBatch,
        ?int $totalPages,
    ): array {
        $items = $firstBatch;
        $page  = 2;

        do {
            if ($totalPages !== null && $page > $totalPages) {
                break;
            }

            $response = $this->get($endpoint . $sep . "limit={$limit}&page={$page}");
            $fetched  = $response['items'] ?? $response['data'] ?? [];
            $items    = array_merge($items, $fetched);
            $page++;
        } while (count($fetched) === $limit);

        return $items;
    }

    /**
     * Return true when an endpoint should have its GET response cached.
     */
    private function isCacheableEndpoint(string $endpoint): bool
    {
        foreach (self::CACHEABLE_PREFIXES as $prefix) {
            if (str_starts_with($endpoint, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
