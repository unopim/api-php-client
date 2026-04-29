# Examples

Runnable scripts demonstrating the client against a real UnoPim instance.

## Setup

1. Copy `.env.example` to `.env` and fill in your UnoPim URL + credentials.
2. From the repo root: `composer install`.

## Running

```bash
# Load env vars (bash/zsh)
set -a; source examples/.env; set +a

php examples/01-quick-start.php
php examples/02-create-product.php DEMO-001
php examples/03-paginate.php
```

| File | Purpose |
|------|---------|
| `01-quick-start.php` | Connect, list locales, optionally fetch one product by SKU |
| `02-create-product.php` | Create a simple product with channel-locale-scoped values |
| `03-paginate.php` | Stream all products via the auto-paginating generator |
