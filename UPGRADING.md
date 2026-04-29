# Upgrading

## From `webkul/unopim-php-sdk` 1.x → `unopim/api-php-client` 1.0

The package was renamed and the namespace changed.

### Composer

```diff
-    "webkul/unopim-php-sdk": "^1.0",
+    "unopim/api-php-client": "^1.0",
```

The old name is kept as a Composer `replace` alias, so transitive deps continue to resolve. Update direct requires explicitly.

### Namespace

Find/replace across your codebase:

```diff
-use Webkul\UnoPim\UnoPimClient;
+use Unopim\ApiClient\UnoPimClient;
```

```diff
-use Webkul\UnoPim\Exception\ApiException;
+use Unopim\ApiClient\Exception\ApiException;
```

```bash
# Bulk rename in a Unix shell:
find . -type f -name "*.php" -not -path "./vendor/*" \
  -exec sed -i 's/Webkul\\UnoPim\\/Unopim\\ApiClient\\/g' {} +
```

### Public API

No breaking changes to method signatures, return types, or behavior — only the package name + namespace moved.

### Re-install

```bash
composer remove webkul/unopim-php-sdk   # if explicitly required
composer require unopim/api-php-client
composer update --lock
```

### Verify

```bash
php -r 'echo class_exists(\\Unopim\\ApiClient\\UnoPimClient::class) ? "OK" : "FAIL";'
```
