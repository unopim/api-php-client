# UnoPim PHP API Client

[![Tests](https://github.com/unopim/api-php-client/actions/workflows/tests.yml/badge.svg)](https://github.com/unopim/api-php-client/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/github/license/unopim/api-php-client.svg)](LICENSE)

Official PHP client for the [UnoPim](https://unopim.com) PIM REST API.

Works in **any PHP project** — Laravel, Symfony, WordPress, Magento, Drupal, plain PHP.

---

## Install

```bash
composer require unopim/api-php-client
```

That's it. The bundled cURL client works out of the box — no extra HTTP package needed. Want Guzzle or Symfony HttpClient instead? See [HTTP clients](#using-other-http-clients).

## Connect

```php
use Unopim\ApiClient\UnoPimClient;

$client = UnoPimClient::create(
    baseUrl:      'https://your-unopim.example.com',
    clientId:     'your-client-id',
    clientSecret: 'your-client-secret',
    username:     'admin@example.com',
    password:     'your-admin-password',
);
```

> **Where do credentials come from?**
> In UnoPim admin: **System → API Clients → Create**. Note the Client ID & Secret. Username / password = any admin user's login.

---

## Examples

### List all products

```php
foreach ($client->products()->iterate() as $product) {
    echo $product['sku'], PHP_EOL;
}
```

### Get one product

```php
$product = $client->products()->get('MY-SKU-001');
```

### Create a product

```php
$client->products()->create([
    'sku'        => 'NEW-SKU',
    'parent'     => null,
    'family'     => 'default',
    'type'       => 'simple',
    'additional' => null,
    'values'     => [
        'common' => [
            'sku'  => 'NEW-SKU',
            'name' => 'My Product',
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
```

### Update a product

```php
$client->products()->update('NEW-SKU', [
    'sku'    => 'NEW-SKU',
    'family' => 'default',
    'type'   => 'simple',
    'values' => [
        'common' => ['sku' => 'NEW-SKU', 'name' => 'Updated Name'],
    ],
]);
```

### Delete a product

```php
$client->products()->delete('NEW-SKU');
```

### Categories, attributes, families…

Same shape — `$client->categories()`, `$client->attributes()`, `$client->attributeFamilies()`, etc.

```php
// All categories
$categories = $client->categories()->list();

// Create attribute
$client->attributes()->create([
    'code'        => 'color',
    'type'        => 'select',
    'labels'      => ['en_US' => 'Color'],
    'is_unique'   => false,
    'is_required' => false,
]);

// Add options to it
$client->attributes()->createOptions('color', [
    ['code' => 'red',  'labels' => ['en_US' => 'Red']],
    ['code' => 'blue', 'labels' => ['en_US' => 'Blue']],
]);
```

### Upload a product image

```php
$client->mediaFiles()->uploadProductMedia('/path/to/image.jpg');
```

### Raw call to any endpoint

If a resource isn't wrapped yet:

```php
$client->get('/api/v1/rest/some-endpoint');
$client->post('/api/v1/rest/some-endpoint', $payload);
$client->put('/api/v1/rest/some-endpoint/code', $payload);
$client->delete('/api/v1/rest/some-endpoint/code');

// Auto-paginates
$all = $client->fetchAll('/api/v1/rest/products', batchSize: 100);
```

More runnable scripts in [`examples/`](examples/).

---

## Available resources

| Accessor | Methods |
|---|---|
| `$client->locales()` | `list()`, `get($code)` |
| `$client->currencies()` | `list()`, `get($code)` |
| `$client->channels()` | `list()`, `get($code)` |
| `$client->categories()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->categoryFields()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->attributes()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)`, `listOptions($code)`, `createOptions($code, $data)`, `updateOptions($code, $data)` |
| `$client->attributeGroups()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->attributeFamilies()` | `list()`, `get($code)`, `create($data)`, `update($code, $data)` |
| `$client->products()` | `list()`, `iterate()`, `get($sku)`, `create($data)`, `update($sku, $data)`, `delete($sku)` |
| `$client->configurableProducts()` | `list()`, `iterate()`, `get($sku)`, `create($data)`, `update($sku, $data)` |
| `$client->mediaFiles()` | `uploadProductMedia($filePath)`, `uploadCategoryMedia($filePath)` |

---

## Errors

```php
use Unopim\ApiClient\Exception\ApiException;
use Unopim\ApiClient\Exception\AuthenticationException;

try {
    $client->products()->get('MISSING-SKU');
} catch (AuthenticationException $e) {
    // 401 — bad credentials
} catch (ApiException $e) {
    $e->getStatusCode();   // int
    $e->getResponseBody(); // string
    $e->getMessage();
}
```

---

## Using other HTTP clients

The bundled cURL adapter works for everyone. If you want Guzzle, Symfony HttpClient, or any other PSR-18 client:

### Guzzle

```bash
composer require guzzlehttp/guzzle guzzlehttp/psr7
```

```php
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\HttpFactory;
use Unopim\ApiClient\UnoPimClient;

$g = new Guzzle(['timeout' => 30]);
$f = new HttpFactory();

$client = UnoPimClient::createWithHttpClient(
    'https://your-unopim.example.com', 'cid', 'csec', 'admin@example.com', 'pass',
    $g, $f, $f,
);
```

### Symfony HttpClient

```bash
composer require symfony/http-client nyholm/psr7
```

```php
use Symfony\Component\HttpClient\Psr18Client;
use Unopim\ApiClient\UnoPimClient;

$s = new Psr18Client();
$client = UnoPimClient::createWithHttpClient(
    'https://your-unopim.example.com', 'cid', 'csec', 'admin@example.com', 'pass',
    $s, $s, $s,
);
```

---

## Requirements

- PHP **8.1+**
- `ext-curl`, `ext-json`
- UnoPim server **v2.0+**

## License

MIT — see [LICENSE](LICENSE).

## Issues / Contributing

[Open an issue](https://github.com/unopim/api-php-client/issues) or send a PR. See [CONTRIBUTING.md](CONTRIBUTING.md).

Maintained by [Webkul Software Pvt. Ltd.](https://webkul.com)
