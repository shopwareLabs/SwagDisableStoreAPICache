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

## Cache invalidation

The HTTP cache is tagged independently of the object cache: the underlying routes
dispatch `AddCacheTagEvent` themselves, and the `CacheInvalidationSubscriber` purges
HTTP entries by those tags. So removing the object cache does **not** break HTTP
invalidation for navigation, category, landing page or sitemap — they already emit the
exact tag name the invalidation subscriber targets.

Four product routes are the exception: in legacy mode the invalidation subscriber purges
them by the decorator route name (e.g. `product-detail-route-{id}`), which only the
removed object-cache decorator used to attach. To keep invalidation correct, this plugin
ships small decorators on the real routes that re-emit those legacy tags:

| Route | Re-emitted tag |
|---|---|
| `ProductDetailRoute` | `product-detail-route-{parentId}` |
| `ProductListingRoute` | `product-listing-route-{categoryId}` |
| `ProductReviewRoute` | `product-review-route-{productId}` |
| `ProductCrossSellingRoute` | `cross-selling-route-{productId}` |

These decorators only dispatch the tag and delegate straight to the inner route — they
do not cache anything.

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

## Tests

```bash
# Unit tests (no database required)
vendor/bin/phpunit -c phpunit.xml.dist

# Integration tests (boots the kernel, installs the plugin; requires a test database)
vendor/bin/phpunit -c phpunit.integration.xml.dist
```

## Compatibility

- Requires **Shopware 6.6** (`shopware/core: ~6.6.0`).
- **Not needed on Shopware 6.7+.** These `Cached*Route` decorators are deprecated in
  6.6 (`@deprecated tag:v6.7.0 - reason:decoration-will-be-removed`) and have been
  **removed entirely in 6.7** as part of the cache rework — there is no store-api object
  cache layer to disable there. Do not install this plugin on 6.7 or later.

## License

MIT — see [LICENSE](LICENSE).
