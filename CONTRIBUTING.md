# Contributing

Thanks for considering a contribution to the UnoPim PHP API Client.

## Reporting Issues

- Search [existing issues](https://github.com/unopim/api-php-client/issues) before opening a new one.
- Include: PHP version, client version, reproduction snippet, expected vs actual behavior.
- Redact secrets (client_id / client_secret / tokens / URLs containing credentials).

## Development Setup

```bash
git clone https://github.com/unopim/api-php-client.git
cd api-php-client
composer install
composer test
```

## Pull Requests

1. Fork the repo and create a topic branch from `main`:
   `git checkout -b fix/short-description`
2. Add or update tests covering your change.
3. Run the full check suite locally:
   ```bash
   composer test
   composer stan
   composer cs
   ```
4. Commit using [Conventional Commits](https://www.conventionalcommits.org/):
   - `feat: add foo endpoint`
   - `fix: handle empty paginated response`
   - `docs: clarify auth setup`
5. Push and open a PR against `main`. Link related issues in the PR body.

## Coding Standards

- PSR-12, enforced by `php-cs-fixer`.
- Strict types declared at the top of every file.
- Public API methods: typed parameters and return types, full PHPDoc.
- Throw typed exceptions from `Unopim\ApiClient\Exception\*`, never raw `\Exception`.
- New endpoints belong in `src/Api/<Resource>Api.php`.

## Tests

- All new code paths must be covered by Pest tests in `tests/`.
- Mock HTTP responses; do not hit the live UnoPim API in CI.
- Run a single file: `vendor/bin/pest tests/Api/ProductApiTest.php`.

## Releases

- Maintainers tag releases via `git tag vX.Y.Z && git push --tags`.
- `CHANGELOG.md` must be updated before tagging.

## Code of Conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md). By participating you agree to abide by it.
