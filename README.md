# UnoPim PHP API Client

[![Tests](https://github.com/unopim/api-php-client/actions/workflows/tests.yml/badge.svg)](https://github.com/unopim/api-php-client/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/unopim/api-php-client.svg)](https://packagist.org/packages/unopim/api-php-client)
[![License](https://img.shields.io/packagist/l/unopim/api-php-client.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/unopim/api-php-client.svg)](composer.json)

Official PHP API client for the [UnoPim](https://unopim.com) PIM REST API.

Framework-agnostic. Works with **Laravel, Symfony, WordPress, Magento, Drupal, plain PHP** — anything with Composer.

Built against **PSR-18** (HTTP Client), **PSR-7** (HTTP Messages), and **PSR-17** (HTTP Factories). Ships a zero-dependency built-in cURL client so you can start without installing any extra HTTP package.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [HTTP Client Adapters](#http-client-adapters)
- [Available Resources](#available-resources)
- [Pagination](#pagination)
- [Error Handling](#error-handling)
- [Caching](#caching)
- [Architecture](#architecture)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Requirements

- PHP **8.1+**
- `ext-curl`, `ext-json`
- A reachable UnoPim instance with an OAuth API client created (see [Generating Credentials](#generating-credentials))

## Installation

```bash
composer require unopim/api-php-client
```

That's it — the bundled cURL adapter satisfies the PSR-18/PSR-17 requirements out of the box.

> **Migrating from `webkul/unopim-php-sdk`?** The package was renamed in v1.0. The old name is kept as a Composer `replace` alias, so existing `require` lines keep resolving — but please update to `unopim/api-php-client` and the `Unopim\ApiClient\` namespace.

## Quick Start

```php
use Unopim\ApiClient\UnoPimClient;

$client = UnoPimClient::create(
    baseUrl:      'https://your-unopim.example.com',
    clientId:     'your-client-id',
    clientSecret: 'your-client-secret',
    username:     'admin@example.com',
    password:     'your-admin-password',
);

// List products (auto-paginated, returns iterator over all pages)
foreach ($client->products()->list() as $product) {
    echo $product['sku'] . PHP_EOL;
}

// Get a single product
$product = $client->products()->get('MY-SKU-001');

// Create a product
$client->products()->create([
    'sku'        => 'NEW-SKU',
    'parent'     => null,
    'family'     => 'default',
    'type'       => 'simple',
    'additional' => null,
    'values'     => [
        'common' => [
            'sku'  => 'NEW-SKU',
            'name' => 'My New Product',
        ],
        'channel_locale_specific' => [
            'ecommerce' => [
                'en_US' => [
                    'description' => '<p>Hello world.</p>',
                    'price'       => '{"USD":"49.99"}',
                ],
            ],
        ],
    ],
]);

// Update a product
$client->products()->update('NEW-SKU', [
    'sku'    => 'NEW-SKU',
    'family' => 'default',
    'type'   => 'simple',
    'values' => [
        'common' => [
            'sku'  => 'NEW-SKU',
            'name' => 'Updated Name',
        ],
    ],
]);
```

## Authentication

UnoPim's REST API requires **OAuth 2.0 password grant**. The client handles the auth dance for you:

- Tokens fetched on first authenticated request.
- Cached **in memory** for the request lifetime.
- Auto-refreshed **60 seconds before expiry**.

For long-running workers (queue consumers, daemons), persist tokens by extending `Unopim\ApiClient\Auth\TokenStore` (see [Custom Token Storage](#custom-token-storage)).

### Generating Credentials

In your UnoPim admin:

1. Go to **System → API Clients → Create**.
2. Note the **Client ID** and **Client Secret** shown once at creation.
3. Use any admin user's email + password as `username` / `password`.

## HTTP Client Adapters

The client depends only on PSR interfaces. Pass any PSR-18 client + PSR-17 factories.

### Built-in cURL (default)

```php
$client = UnoPimClient::create($baseUrl, $clientId, $clientSecret, $username, $password);
```

### Guzzle 7

```bash
composer require guzzlehttp/guzzle guzzlehttp/psr7
```

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Unopim\ApiClient\UnoPimClient;

$guzzle  = new GuzzleClient(['timeout' => 30]);
$factory = new HttpFactory();

$client = UnoPimClient::createWithHttpClient(
    baseUrl:        'https://your-unopim.example.com',
    clientId:       'your-client-id',
    clientSecret:   'your-client-secret',
    username:       'admin@example.com',
    password:       'your-admin-password',
    httpClient:     $guzzle,
    requestFactory: $factory,
    streamFactory:  $factory,
);
```

### Symfony HttpClient

```bash
composer require symfony/http-client nyholm/psr7
```

```php
use Symfony\Component\HttpClient\Psr18Client;
use Unopim\ApiClient\UnoPimClient;

$symfony = new Psr18Client(); // implements ClientInterface + RequestFactoryInterface + StreamFactoryInterface

$client = UnoPimClient::createWithHttpClient(
    baseUrl:        'https://your-unopim.example.com',
    clientId:       'your-client-id',
    clientSecret:   'your-client-secret',
    username:       'admin@example.com',
    password:       'your-admin-password',
    httpClient:     $symfony,
    requestFactory: $symfony,
    streamFactory:  $symfony,
);
```

### Mock client (testing)

Inject `php-http/mock-client`, your own fake, or any PSR-18 implementation — no application code change required.

## Available Resources

| Accessor | Available methods |
|---|---|
| `$client->locales()` | `list()`, `get($code)` |
| `$client->currencies()` | `list()`, `get($code)` |
| `$client->channels()` | `list()`, `get($code)` |
| `$client->categories()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->categoryFields()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->attributes()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)`, `listOptions($code)`, `createOptions($code, $data)`, `updateOptions($code, $data)` |
| `$client->attributeGroups()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->attributeFamilies()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->products()` | `list()`, `get($sku)`, `create($data)`, `update($sku, $data)`, `delete($sku)` |
| `$client->configurableProducts()` | `list()`, `get($sku)`, `create($data)`, `update($sku, $data)` |
| `$client->mediaFiles()` | `uploadProductMedia($filePath)`, `uploadCategoryMedia($filePath)` |

Generic raw-call helpers when no resource wrapper exists yet:

```php
$client->get('/api/v1/rest/some-endpoint');
$client->post('/api/v1/rest/some-endpoint', $payload);
$client->put('/api/v1/rest/some-endpoint/code', $payload);
$client->delete('/api/v1/rest/some-endpoint/code');

// Auto-paginates and merges all pages into one array
$all = $client->fetchAll('/api/v1/rest/products', batchSize: 100);
```

## Pagination

All `list()` methods and `fetchAll()` paginate automatically using UnoPim's `current_page` / `last_page` metadata. Default page size is **100** (the API maximum). Override per call:

```php
$products = $client->fetchAll('/api/v1/rest/products', batchSize: 50);
```

For large catalogs, prefer streaming via `list()` (returns generator) over loading everything into memory.

## Error Handling

```php
use Unopim\ApiClient\Exception\ApiException;
use Unopim\ApiClient\Exception\AuthenticationException;
use Unopim\ApiClient\Exception\UnoPimException; // base exception

try {
    $product = $client->products()->get('MISSING-SKU');
} catch (AuthenticationException $e) {
    // 401 — bad credentials or expired token (after retry)
} catch (ApiException $e) {
    // Any non-2xx response. Inspect:
    $e->getStatusCode();   // int — HTTP status
    $e->getResponseBody(); // string — raw response body
    $e->getMessage();      // string — human-readable summary
} catch (UnoPimException $e) {
    // Network errors, JSON parse errors, etc.
}
```

## Caching

Read-only endpoints (locales, currencies, channels, attribute scopes) are cached **in-memory per client instance** for `cacheTtl` seconds (default **300**). Disable with `cacheTtl: 0`:

```php
$client = UnoPimClient::create($baseUrl, $clientId, $clientSecret, $username, $password, cacheTtl: 0);
```

For cross-process caching (Redis, APCu), implement your own and wrap calls externally. A pluggable cache contract is on the v1.1 roadmap.

## Custom Token Storage

The default `TokenStore` keeps tokens in memory. For long-running daemons or multi-process workers, extend it and store the token in your cache backend of choice:

```php
use Unopim\ApiClient\Auth\TokenStore;

class RedisTokenStore extends TokenStore {
    public function __construct(private \Redis $redis, private string $key) {}

    public function get(): ?string { return $this->redis->get($this->key) ?: null; }

    public function put(string $token, int $expiresAt): void {
        $this->redis->set($this->key, $token, ['EX' => max(1, $expiresAt - time())]);
    }
}
```

(Wiring your own TokenStore via constructor injection is on the v1.1 roadmap; for now subclass + override.)

## Architecture

```
src/
├── UnoPimClient.php              # Main entry point — accepts PSR-18/PSR-17
├── Auth/
│   └── TokenStore.php            # In-memory OAuth token cache
├── Cache/
│   └── ResponseCache.php         # Per-instance read-only cache
├── Api/
│   ├── AbstractApi.php           # Shared get/post/put/delete + fetchAll helpers
│   ├── AttributeApi.php
│   ├── AttributeFamilyApi.php
│   ├── AttributeGroupApi.php
│   ├── CategoryApi.php
│   ├── CategoryFieldApi.php
│   ├── ChannelApi.php
│   ├── ConfigurableProductApi.php
│   ├── CurrencyApi.php
│   ├── LocaleApi.php
│   ├── MediaFileApi.php
│   └── ProductApi.php
├── Http/
│   ├── CurlClient.php            # Built-in PSR-18 ClientInterface (cURL)
│   ├── CurlHttpClient.php        # Lower-level cURL wrapper
│   └── Psr7/
│       ├── Request.php
│       ├── Response.php
│       ├── Stream.php
│       ├── Uri.php
│       ├── MessageTrait.php
│       ├── RequestFactory.php
│       ├── ResponseFactory.php
│       └── StreamFactory.php
└── Exception/
    ├── UnoPimException.php       # Base exception
    ├── ApiException.php          # Non-2xx HTTP responses
    └── AuthenticationException.php
```

The client targets **PSR interfaces only** — never concrete Guzzle/Symfony classes. The built-in `CurlClient` exists so you can use the client with zero extra dependencies.

## Testing

```bash
composer test           # Pest unit + feature tests
composer test:cov       # with coverage
composer stan           # PHPStan level 5
composer cs             # PSR-12 check (dry-run)
composer cs:fix         # PSR-12 auto-fix
```

CI runs the matrix on PHP 8.1, 8.2, 8.3 against highest + lowest dependency versions on every push/PR.

## Compatibility

| client | UnoPim server | PHP |
|-----|---------------|-----|
| 1.x | v2.0+         | 8.1+ |

## Contributing

PRs welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

Bug? Open an [issue](https://github.com/unopim/api-php-client/issues). Security vulnerability? See [SECURITY.md](SECURITY.md).

## License

MIT — see [LICENSE](LICENSE).

Maintained by [Webkul Software Pvt. Ltd.](https://webkul.com) and the UnoPim community.
