<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the store-api object cache decorators (`Cached*Route`).
 */
class DisableStoreApiCacheCompilerPass implements CompilerPassInterface
{
    /**
     * Store-api object cache decorator service ids. The service id equals the
     * fully-qualified class name in every case.
     *
     * @var list<string>
     */
    private const CACHE_DECORATORS = [
        // Product
        \Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute::class,
        \Shopware\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute::class,
        \Shopware\Core\Content\Product\SalesChannel\Search\CachedProductSearchRoute::class,
        \Shopware\Core\Content\Product\SalesChannel\Suggest\CachedProductSuggestRoute::class,
        \Shopware\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute::class,
        \Shopware\Core\Content\Product\SalesChannel\CrossSelling\CachedProductCrossSellingRoute::class,
        // Category / Navigation / Landing page
        \Shopware\Core\Content\Category\SalesChannel\CachedNavigationRoute::class,
        \Shopware\Core\Content\Category\SalesChannel\CachedCategoryRoute::class,
        \Shopware\Core\Content\LandingPage\SalesChannel\CachedLandingPageRoute::class,
        // Sitemap
        \Shopware\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::CACHE_DECORATORS as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->removeDefinition($serviceId);
            }
        }
    }
}
