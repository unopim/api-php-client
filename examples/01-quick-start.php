<?php

/**
 * Quick start: connect, list locales, fetch a single product.
 *
 * Usage: php examples/01-quick-start.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Unopim\ApiClient\Exception\ApiException;
use Unopim\ApiClient\UnoPimClient;

$client = UnoPimClient::create(
    baseUrl:      $_ENV['UNOPIM_URL']           ?? 'http://localhost:8000',
    clientId:     $_ENV['UNOPIM_CLIENT_ID']     ?? '',
    clientSecret: $_ENV['UNOPIM_CLIENT_SECRET'] ?? '',
    username:     $_ENV['UNOPIM_USERNAME']      ?? 'admin@example.com',
    password:     $_ENV['UNOPIM_PASSWORD']      ?? '',
);

try {
    $locales = $client->locales()->list();
    echo 'Locales: ' . implode(', ', array_column($locales, 'code')) . PHP_EOL;

    $sku     = $argv[1] ?? null;
    if ($sku) {
        $product = $client->products()->get($sku);
        echo 'Product ' . $sku . ":\n" . json_encode($product, JSON_PRETTY_PRINT) . PHP_EOL;
    }
} catch (ApiException $e) {
    fwrite(STDERR, "API error {$e->getStatusCode()}: {$e->getMessage()}\n");
    exit(1);
}
