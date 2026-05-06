# Changelog

All notable changes to `unopim/api-php-client` will be documented here.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
and [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.1] — 2026-05-06

### Fixed

* Media uploads now support `sku`/`code` identifiers, along with additional attribute fields.

### Added

* HTTP helpers:

  * `UnoPimClient`: `patch()`, `delete()`
  * `AbstractApi`: `patch()`, `delete()` passthroughs
* API support:

  * `CategoryApi`: `patch()`, `delete()`
  * `ProductApi`: `patch()`, `delete()`
  * `ConfigurableProductApi`: `patch()`
* `CategoryFieldApi`: added `listOptions`, `createOptions`, and `updateOptions` (now aligned with `AttributeApi`)
* `MediaFileApi`: `uploadSwatchMedia()` for attribute option swatches

### Improved

* Parity between `CategoryFieldApi` and `AttributeApi` option management methods.


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

[1.0.0]: https://github.com/unopim/api-php-client/releases/tag/v1.0.0
[1.0.1]: https://github.com/unopim/api-php-client/releases/tag/v1.0.1
