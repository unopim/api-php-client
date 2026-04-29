<?php

declare(strict_types=1);

namespace Unopim\ApiClient\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Unopim\ApiClient\Exception\ApiException;
use Unopim\ApiClient\Http\Psr7\Response;

/**
 * PSR-18 ClientInterface implementation using native cURL — zero external dependencies.
 *
 * This client is bundled with the API client so consumers do not need to install a
 * separate HTTP client package. Users who prefer Guzzle, Symfony HttpClient, or
 * any other PSR-18-compatible client can pass it to UnoPimClient::createWithHttpClient().
 *
 * Features:
 * - Persistent connection reuse via a single cURL handle (curl_reset between requests)
 * - TCP keep-alive to avoid reconnect overhead on large paginated fetches
 * - Parallel multi-request execution via curl_multi_* (sendMultiple)
 * - HTTP/2 multiplexing when available (falls back gracefully)
 */
final class CurlClient implements ClientInterface
{
    private int $timeout;

    private bool $verifySsl;

    private int $batchSize;

    /** @var \CurlHandle|resource|null */
    private $handle = null;

    public function __construct(int $timeout = 30, bool $verifySsl = true, int $batchSize = 10)
    {
        $this->timeout   = $timeout;
        $this->verifySsl = $verifySsl;
        $this->batchSize = max(1, $batchSize);
    }

    public function __destruct()
    {
        if ($this->handle !== null) {
            curl_close($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Send a PSR-7 request and return a PSR-7 response.
     *
     * The underlying cURL handle is reused across calls (curl_reset) to benefit
     * from TCP keep-alive and connection reuse without re-negotiating TLS.
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->handle === null) {
            $this->handle = curl_init();
        } else {
            curl_reset($this->handle);
        }

        $ch = $this->handle;

        [$responseStatus, $responseHeaders, $responseBody] = $this->execHandle($ch, $request);

        return new Response(
            statusCode:      $responseStatus,
            headers:         $responseHeaders,
            body:            $responseBody,
            protocolVersion: '1.1',
        );
    }

    /**
     * Send multiple PSR-7 requests in parallel using curl_multi_* and return
     * an array of PSR-7 responses in the same order as the input array.
     *
     * Requests are processed in configurable batches (default 10 concurrent) to
     * avoid exhausting file descriptors on large page sets. HTTP/2 multiplexing
     * is requested when available; the library falls back gracefully if not.
     *
     * @param  RequestInterface[]         $requests
     * @return ResponseInterface[]
     *
     * @throws ApiException
     */
    public function sendMultiple(array $requests): array
    {
        if (empty($requests)) {
            return [];
        }

        $responses = array_fill(0, count($requests), null);
        $batches   = array_chunk(array_keys($requests), $this->batchSize, true);

        foreach ($batches as $batch) {
            $mh      = curl_multi_init();
            $handles = [];

            foreach ($batch as $index) {
                $request = $requests[$index];

                $responseStatus  = 200;
                $responseHeaders = [];

                $ch = curl_init();

                $curlHeaders = [];

                foreach ($request->getHeaders() as $name => $values) {
                    $curlHeaders[] = $name . ': ' . implode(', ', $values);
                }

                $opts = [
                    CURLOPT_URL            => (string) $request->getUri(),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => $this->timeout,
                    CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
                    CURLOPT_HTTPHEADER     => $curlHeaders,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
                    CURLOPT_PIPEWAIT       => true,
                    CURLOPT_TCP_KEEPALIVE  => 1,
                    CURLOPT_TCP_KEEPIDLE   => 30,
                    CURLOPT_TCP_KEEPINTVL  => 15,
                    CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseStatus, &$responseHeaders): int {
                        $len = strlen($header);

                        if (str_starts_with($header, 'HTTP/')) {
                            $parts          = explode(' ', $header, 3);
                            $responseStatus = isset($parts[1]) ? (int) $parts[1] : 200;

                            return $len;
                        }

                        $parts = explode(':', $header, 2);

                        if (count($parts) === 2) {
                            $responseHeaders[trim($parts[0])][] = trim($parts[1]);
                        }

                        return $len;
                    },
                ];

                $method = strtoupper($request->getMethod());
                $body   = (string) $request->getBody();

                curl_setopt_array($ch, $opts);

                match ($method) {
                    'POST'   => curl_setopt_array($ch, [
                        CURLOPT_POST       => true,
                        CURLOPT_POSTFIELDS => $body,
                    ]),
                    'PUT'    => curl_setopt_array($ch, [
                        CURLOPT_CUSTOMREQUEST => 'PUT',
                        CURLOPT_POSTFIELDS    => $body,
                    ]),
                    'PATCH'  => curl_setopt_array($ch, [
                        CURLOPT_CUSTOMREQUEST => 'PATCH',
                        CURLOPT_POSTFIELDS    => $body,
                    ]),
                    'DELETE' => curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'),
                    'HEAD'   => curl_setopt($ch, CURLOPT_NOBODY, true),
                    default  => null,
                };

                curl_multi_add_handle($mh, $ch);

                $handles[$index] = [
                    'ch'              => $ch,
                    'responseStatus'  => &$responseStatus,
                    'responseHeaders' => &$responseHeaders,
                ];

                unset($responseStatus, $responseHeaders);
            }

            // Execute all handles in the batch
            $running = null;

            do {
                $status = curl_multi_exec($mh, $running);

                if ($status !== CURLM_OK) {
                    break;
                }

                if ($running > 0) {
                    curl_multi_select($mh, 1.0);
                }
            } while ($running > 0);

            // Collect results
            foreach ($handles as $index => $meta) {
                $ch    = $meta['ch'];
                $errno = curl_errno($ch);
                $error = curl_error($ch);

                if ($errno !== 0) {
                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);
                    curl_multi_close($mh);

                    throw new ApiException(sprintf('cURL multi error %d: %s', $errno, $error));
                }

                $rawBody = curl_multi_getcontent($ch);

                $responses[$index] = new Response(
                    statusCode:      $meta['responseStatus'],
                    headers:         $meta['responseHeaders'],
                    body:            is_string($rawBody) ? $rawBody : '',
                    protocolVersion: '1.1',
                );

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }

            curl_multi_close($mh);
        }

        /** @var ResponseInterface[] $responses */
        return $responses;
    }

    /**
     * Apply options, execute, and collect output for a single cURL handle.
     *
     * @return array{int, array<string, list<string>>, string}
     */
    private function execHandle(\CurlHandle $ch, RequestInterface $request): array
    {
        $responseStatus  = 200;
        $responseHeaders = [];

        $curlHeaders = [];

        foreach ($request->getHeaders() as $name => $values) {
            $curlHeaders[] = $name . ': ' . implode(', ', $values);
        }

        $uri = (string) $request->getUri();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_TCP_KEEPALIVE  => 1,
            CURLOPT_TCP_KEEPIDLE   => 30,
            CURLOPT_TCP_KEEPINTVL  => 15,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseStatus, &$responseHeaders): int {
                $len = strlen($header);

                if (str_starts_with($header, 'HTTP/')) {
                    $parts          = explode(' ', $header, 3);
                    $responseStatus = isset($parts[1]) ? (int) $parts[1] : 200;

                    return $len;
                }

                $parts = explode(':', $header, 2);

                if (count($parts) === 2) {
                    $name  = trim($parts[0]);
                    $value = trim($parts[1]);
                    // Multiple values for the same header are accumulated as a list
                    $responseHeaders[$name][] = $value;
                }

                return $len;
            },
        ]);

        $method = strtoupper($request->getMethod());
        $body   = (string) $request->getBody();

        match ($method) {
            'POST'   => curl_setopt_array($ch, [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => $body,
            ]),
            'PUT'    => curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS    => $body,
            ]),
            'PATCH'  => curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS    => $body,
            ]),
            'DELETE' => curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'),
            'HEAD'   => curl_setopt($ch, CURLOPT_NOBODY, true),
            default  => null, // GET is the default cURL method
        };

        $rawBody = curl_exec($ch);
        $errno   = curl_errno($ch);
        $error   = curl_error($ch);

        if ($errno !== 0) {
            throw new ApiException(sprintf('cURL error %d: %s', $errno, $error));
        }

        return [$responseStatus, $responseHeaders, is_string($rawBody) ? $rawBody : ''];
    }
}
