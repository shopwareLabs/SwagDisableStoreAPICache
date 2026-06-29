<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CachedNavigationRoute;
use Shopware\Core\Content\LandingPage\SalesChannel\CachedLandingPageRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CachedProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\ProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\Search\CachedProductSearchRoute;
use Shopware\Core\Content\Product\SalesChannel\Suggest\CachedProductSuggestRoute;
use Shopware\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\DisableStoreApiCache\Cache\ProductCrossSellingRouteTagDecorator;
use Swag\DisableStoreApiCache\Cache\ProductDetailRouteTagDecorator;
use Swag\DisableStoreApiCache\Cache\ProductListingRouteTagDecorator;
use Swag\DisableStoreApiCache\Cache\ProductReviewRouteTagDecorator;

/**
 * @internal
 */
class RouteCacheDecorationTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @return array<string, array{class-string}>
     */
    public static function cachedRouteProvider(): array
    {
        return [
            'detail' => [CachedProductDetailRoute::class],
            'listing' => [CachedProductListingRoute::class],
            'search' => [CachedProductSearchRoute::class],
            'suggest' => [CachedProductSuggestRoute::class],
            'review' => [CachedProductReviewRoute::class],
            'cross-selling' => [CachedProductCrossSellingRoute::class],
            'navigation' => [CachedNavigationRoute::class],
            'category' => [CachedCategoryRoute::class],
            'landing-page' => [CachedLandingPageRoute::class],
            'sitemap' => [CachedSitemapRoute::class],
        ];
    }

    /**
     * @dataProvider cachedRouteProvider
     *
     * @param class-string $serviceId
     */
    public function testCachedRouteServiceIsRemoved(string $serviceId): void
    {
        static::assertFalse(
            static::getContainer()->has($serviceId),
            sprintf('Object cache decorator %s should be removed from the container', $serviceId)
        );
    }

    /**
     * @return array<string, array{class-string, class-string}>
     */
    public static function tagDecoratorProvider(): array
    {
        return [
            'detail' => [ProductDetailRoute::class, ProductDetailRouteTagDecorator::class],
            'listing' => [ProductListingRoute::class, ProductListingRouteTagDecorator::class],
            'review' => [ProductReviewRoute::class, ProductReviewRouteTagDecorator::class],
            'cross-selling' => [ProductCrossSellingRoute::class, ProductCrossSellingRouteTagDecorator::class],
        ];
    }

    /**
     * @dataProvider tagDecoratorProvider
     *
     * @param class-string $routeId
     * @param class-string $expectedDecorator
     */
    public function testRealRouteIsWrappedByTagDecorator(string $routeId, string $expectedDecorator): void
    {
        $route = static::getContainer()->get($routeId);

        static::assertInstanceOf($expectedDecorator, $route);
    }

    public function testDetailRouteStillResolvesToRealRouteUnderneath(): void
    {
        $route = static::getContainer()->get(ProductDetailRoute::class);

        static::assertInstanceOf(ProductDetailRouteTagDecorator::class, $route);
        static::assertInstanceOf(AbstractProductDetailRoute::class, $route->getDecorated());
    }
}
