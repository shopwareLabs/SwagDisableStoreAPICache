<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Swag\DisableStoreApiCache\Cache\ProductDetailRouteTagDecorator;
use Swag\DisableStoreApiCache\DependencyInjection\DisableStoreApiCacheCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\DecoratorServicePass;
use Symfony\Component\DependencyInjection\Compiler\ResolveDecoratorStackPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Verifies the decorator + compiler-pass interplay: after the cached decorator is
 * removed, our tag decorator becomes the sole decorator of the real route and the
 * decoration stack resolves so that the route service id points to our decorator
 * wrapping the original route.
 *
 * @internal
 */
class DecorationWiringTest extends TestCase
{
    public function testTagDecoratorReplacesCachedDecoratorOnRealRoute(): void
    {
        $container = new ContainerBuilder();
        $container->register('event_dispatcher', EventDispatcher::class)->setPublic(true);

        // Real route (use the abstract as concrete stub so we can instantiate it)
        $real = new Definition(ProductDetailRoute::class);
        $real->setFactory([self::class, 'createRealRouteStub']);
        $real->setPublic(true);
        $container->setDefinition(ProductDetailRoute::class, $real);

        // Core cached decorator (priority -1000)
        $cached = new Definition(CachedProductDetailRoute::class);
        $cached->setDecoratedService(ProductDetailRoute::class, null, -1000);
        $container->setDefinition(CachedProductDetailRoute::class, $cached);

        // Our tag decorator (priority -2000)
        $tag = new Definition(ProductDetailRouteTagDecorator::class);
        $tag->setDecoratedService(ProductDetailRoute::class, null, -2000);
        $tag->setArguments([
            new Reference(ProductDetailRouteTagDecorator::class . '.inner'),
            new Reference('event_dispatcher'),
        ]);
        $tag->setPublic(true);
        $container->setDefinition(ProductDetailRouteTagDecorator::class, $tag);

        // Plugin compiler pass removes the cached decorator before decoration is resolved
        (new DisableStoreApiCacheCompilerPass())->process($container);
        static::assertFalse($container->hasDefinition(CachedProductDetailRoute::class));

        // Resolve decoration exactly like Symfony does at compile time
        (new ResolveDecoratorStackPass())->process($container);
        (new DecoratorServicePass())->process($container);

        $service = $container->get(ProductDetailRoute::class);

        static::assertInstanceOf(ProductDetailRouteTagDecorator::class, $service);
        static::assertInstanceOf(ProductDetailRoute::class, $service->getDecorated());
    }

    public static function createRealRouteStub(): AbstractProductDetailRoute
    {
        return new class extends ProductDetailRoute {
            public function __construct()
            {
            }
        };
    }
}
