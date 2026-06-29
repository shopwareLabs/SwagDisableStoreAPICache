<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CachedNavigationRoute;
use Shopware\Core\Content\LandingPage\SalesChannel\CachedLandingPageRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CachedProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\Search\CachedProductSearchRoute;
use Shopware\Core\Content\Product\SalesChannel\Suggest\CachedProductSuggestRoute;
use Shopware\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute;
use Swag\DisableStoreApiCache\DependencyInjection\DisableStoreApiCacheCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
class DisableStoreApiCacheCompilerPassTest extends TestCase
{
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
    public function testRemovesCachedRouteDefinition(string $serviceId): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition($serviceId, new Definition());

        static::assertTrue($container->hasDefinition($serviceId));

        (new DisableStoreApiCacheCompilerPass())->process($container);

        static::assertFalse(
            $container->hasDefinition($serviceId),
            sprintf('Expected %s definition to be removed', $serviceId)
        );
    }

    public function testDoesNotFailWhenDefinitionMissing(): void
    {
        $container = new ContainerBuilder();

        (new DisableStoreApiCacheCompilerPass())->process($container);

        static::assertFalse($container->hasDefinition(CachedProductDetailRoute::class));
    }

    public function testKeepsUnrelatedDefinitions(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('some.unrelated.service', new Definition());

        (new DisableStoreApiCacheCompilerPass())->process($container);

        static::assertTrue($container->hasDefinition('some.unrelated.service'));
    }

    public function testKeepsCachedRouteWhenAnotherServiceDecoratesIt(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CachedProductDetailRoute::class, new Definition());

        $foreign = new Definition();
        $foreign->setDecoratedService(CachedProductDetailRoute::class);
        $container->setDefinition('foreign.plugin.decorator', $foreign);

        (new DisableStoreApiCacheCompilerPass())->process($container);

        static::assertTrue(
            $container->hasDefinition(CachedProductDetailRoute::class),
            'Cache route must be kept when a foreign service decorates it directly'
        );
    }

    public function testStillRemovesCachedRouteWhenForeignServiceDecoratesTheRealRoute(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CachedProductDetailRoute::class, new Definition());

        // A foreign decorator on the *real* route does not block removal: it keeps
        // wrapping the real route once the cache decorator is gone.
        $foreign = new Definition();
        $foreign->setDecoratedService('Shopware\\Core\\Content\\Product\\SalesChannel\\Detail\\ProductDetailRoute');
        $container->setDefinition('foreign.plugin.decorator', $foreign);

        (new DisableStoreApiCacheCompilerPass())->process($container);

        static::assertFalse($container->hasDefinition(CachedProductDetailRoute::class));
    }
}
