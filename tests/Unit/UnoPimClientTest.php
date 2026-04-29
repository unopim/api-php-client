<?php

declare(strict_types=1);

use Unopim\ApiClient\UnoPimClient;

it('exposes static factories', function () {
    expect(method_exists(UnoPimClient::class, 'create'))->toBeTrue()
        ->and(method_exists(UnoPimClient::class, 'createWithHttpClient'))->toBeTrue();
});

it('builds a client with default cURL adapter', function () {
    $client = UnoPimClient::create(
        baseUrl:      'https://example.test',
        clientId:     'cid',
        clientSecret: 'csec',
        username:     'u',
        password:     'p',
    );
    expect($client)->toBeInstanceOf(UnoPimClient::class);
});

it('exposes all resource accessors', function () {
    $client = UnoPimClient::create('https://example.test', 'cid', 'csec', 'u', 'p');

    foreach (['locales', 'currencies', 'channels', 'categories', 'attributes',
              'attributeGroups', 'attributeFamilies', 'products', 'configurableProducts'] as $method) {
        expect(method_exists($client, $method))->toBeTrue("Missing accessor: $method");
    }
});
