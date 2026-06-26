# SwagDisableStoreAPICache

Disables the **store-api object cache** layer in Shopware 6.6.

## Why

Many store-api routes have a cache decorator — e.g. `CachedProductDetailRoute` wraps
`ProductDetailRoute` and stores responses in the `cache.object` pool. This plugin
disables the content routes whose payloads are heavy and fully covered by an HTTP cache:

- the product routes (detail, listing, search, suggest, review, cross-selling),
- navigation and category,
- landing page, and
- sitemap.

The lightweight system/checkout routes (language, country, currency, salutation,
payment, shipping) are intentionally left cached.

When an **HTTP cache** (reverse proxy such as Varnish/Fastly, or the Symfony HTTP
cache) sits in front of the shop, store-api responses are already cached at the HTTP
layer. The object cache underneath then only adds:

- serialization / compression overhead on every request,
- an extra cache roundtrip, and
- a second invalidation surface that has to be kept in sync.

This plugin removes that redundant layer so the original, undecorated routes are used
directly.


## Installation

```bash
composer require swag/disable-store-api-cache
bin/console plugin:refresh
bin/console plugin:install --activate SwagDisableStoreAPICache
bin/console cache:clear
```

Verify the decorators are gone:

```bash
bin/console debug:container CachedProductDetailRoute   # should report "not found"
```

## Compatibility

- Requires **Shopware 6.6** (`shopware/core: ~6.6.0`).
- **Not needed on Shopware 6.7+.** These `Cached*Route` decorators are deprecated in
  6.6 (`@deprecated tag:v6.7.0 - reason:decoration-will-be-removed`) and have been
  **removed entirely in 6.7** as part of the cache rework — there is no store-api object
  cache layer to disable there. Do not install this plugin on 6.7 or later.

## License

MIT — see [LICENSE](LICENSE).
