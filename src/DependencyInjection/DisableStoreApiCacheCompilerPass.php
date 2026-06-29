<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\DependencyInjection;

use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Search\CachedProductSearchRoute;
use Shopware\Core\Content\Product\SalesChannel\Suggest\CachedProductSuggestRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CachedProductCrossSellingRoute;
use Shopware\Core\Content\Category\SalesChannel\CachedNavigationRoute;
use Shopware\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Shopware\Core\Content\LandingPage\SalesChannel\CachedLandingPageRoute;
use Shopware\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute;
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
        CachedProductDetailRoute::class,
        CachedProductListingRoute::class,
        CachedProductSearchRoute::class,
        CachedProductSuggestRoute::class,
        CachedProductReviewRoute::class,
        CachedProductCrossSellingRoute::class,
        // Category / Navigation / Landing page
        CachedNavigationRoute::class,
        CachedCategoryRoute::class,
        CachedLandingPageRoute::class,
        // Sitemap
        CachedSitemapRoute::class,
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
