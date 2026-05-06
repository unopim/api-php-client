# Changelog

All notable changes to `unopim/api-php-client` will be documented here.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
and [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.1] — 2026-05-06

### Fixed
- Media uploads now support `sku`/`code` identifiers and additional attribute fields

## [1.0.0] — 2026-05-01

### Added
- Initial release of the official PHP client for the UnoPim REST API.
- OAuth password-grant authentication with automatic token refresh.
- `UnoPimClient::create()` factory.
- HTTP methods: `get`, `post`, `put`, `delete`, `fetchAll` (paginated).
- Resource APIs:
  - `AttributeApi`
  - `AttributeFamilyApi`
  - `AttributeGroupApi`
  - `CategoryApi`
  - `CategoryFieldApi`
  - `ChannelApi`
  - `LocaleApi`
  - `CurrencyApi`
  - `ProductApi` (simple)
  - `ConfigurableProductApi`
- DTOs for typed access (where helpful).
- Exception hierarchy: `ApiException`, `AuthException`, `ValidationException`, `NotFoundException`.
- In-memory token cache, with `TokenStore` extension point for persistent caching.
- PSR-18 HTTP client abstraction with bundled `CurlHttpClient` fallback.
- Tested against UnoPim v2.0.

[Unreleased]: https://github.com/unopim/api-php-client/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/unopim/api-php-client/releases/tag/v1.0.0
