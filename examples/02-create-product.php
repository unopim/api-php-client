<?php

/**
 * Create a simple product with channel/locale-scoped values.
 *
 * Usage: php examples/02-create-product.php SKU-123
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Unopim\ApiClient\Exception\ApiException;
use Unopim\ApiClient\UnoPimClient;

$sku = $argv[1] ?? exit("Usage: php examples/02-create-product.php <sku>\n");

$client = UnoPimClient::create(
    baseUrl:      $_ENV['UNOPIM_URL']           ?? 'http://localhost:8000',
    clientId:     $_ENV['UNOPIM_CLIENT_ID']     ?? '',
    clientSecret: $_ENV['UNOPIM_CLIENT_SECRET'] ?? '',
    username:     $_ENV['UNOPIM_USERNAME']      ?? 'admin@example.com',
    password:     $_ENV['UNOPIM_PASSWORD']      ?? '',
);

try {
    $client->products()->create([
        'sku'        => $sku,
        'parent'     => null,
        'family'     => 'default',
        'type'       => 'simple',
        'additional' => null,
        'values'     => [
            'common' => [
                'sku'  => $sku,
                'name' => 'Example Product ' . $sku,
            ],
            'channel_locale_specific' => [
                'ecommerce' => [
                    'en_US' => [
                        'description' => '<p>Created via the UnoPim PHP API Client.</p>',
                        'price'       => '{"USD":"19.99"}',
                    ],
                ],
            ],
        ],
    ]);
    echo "Created {$sku}\n";
} catch (ApiException $e) {
    fwrite(STDERR, "API {$e->getStatusCode()}: {$e->getMessage()}\n");
    exit(1);
}
