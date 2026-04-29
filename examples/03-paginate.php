<?php

/**
 * Stream all products without loading them all into memory.
 *
 * Usage: php examples/03-paginate.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Unopim\ApiClient\UnoPimClient;

$client = UnoPimClient::create(
    baseUrl:      $_ENV['UNOPIM_URL']           ?? 'http://localhost:8000',
    clientId:     $_ENV['UNOPIM_CLIENT_ID']     ?? '',
    clientSecret: $_ENV['UNOPIM_CLIENT_SECRET'] ?? '',
    username:     $_ENV['UNOPIM_USERNAME']      ?? 'admin@example.com',
    password:     $_ENV['UNOPIM_PASSWORD']      ?? '',
);

$count = 0;
foreach ($client->products()->list() as $product) {
    $count++;
    if ($count % 100 === 0) {
        echo "Processed {$count} products...\n";
    }
}
echo "Done. Total: {$count}\n";
