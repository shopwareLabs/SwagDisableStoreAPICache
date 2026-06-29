<?php declare(strict_types=1);

namespace Swag\DisableStoreApiCache\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\ProductCrossSellingRouteResponse;
use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRouteResponse;
use Shopware\Core\Content\Product\SalesChannel\Listing\AbstractProductListingRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRouteResponse;
use Shopware\Core\Content\Product\SalesChannel\Review\AbstractProductReviewRoute;
use Shopware\Core\Content\Product\SalesChannel\Review\ProductReviewRouteResponse;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\DisableStoreApiCache\Cache\ProductCrossSellingRouteTagDecorator;
use Swag\DisableStoreApiCache\Cache\ProductDetailRouteTagDecorator;
use Swag\DisableStoreApiCache\Cache\ProductListingRouteTagDecorator;
use Swag\DisableStoreApiCache\Cache\ProductReviewRouteTagDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class ProductRouteTagDecoratorTest extends TestCase
{
    public function testDetailRouteEmitsLegacyTagForParentId(): void
    {
        $product = new SalesChannelProductEntity();
        $product->setId('variant-id');
        $product->setParentId('parent-id');

        $response = new ProductDetailRouteResponse($product, null);

        $inner = $this->createMock(AbstractProductDetailRoute::class);
        $inner->expects(static::once())
            ->method('load')
            ->willReturn($response);

        $decorator = new ProductDetailRouteTagDecorator($inner, $this->dispatcherExpecting('product-detail-route-parent-id'));

        $result = $decorator->load('variant-id', new Request(), $this->context(), new Criteria());

        static::assertSame($response, $result);
    }

    public function testDetailRouteFallsBackToProductIdWhenNoParent(): void
    {
        $product = new SalesChannelProductEntity();
        $product->setId('product-id');

        $response = new ProductDetailRouteResponse($product, null);

        $inner = $this->createMock(AbstractProductDetailRoute::class);
        $inner->method('load')->willReturn($response);

        $decorator = new ProductDetailRouteTagDecorator($inner, $this->dispatcherExpecting('product-detail-route-product-id'));

        $decorator->load('product-id', new Request(), $this->context(), new Criteria());
    }

    public function testListingRouteEmitsLegacyTagForCategoryId(): void
    {
        $response = $this->createMock(ProductListingRouteResponse::class);

        $inner = $this->createMock(AbstractProductListingRoute::class);
        $inner->expects(static::once())->method('load')->willReturn($response);

        $decorator = new ProductListingRouteTagDecorator($inner, $this->dispatcherExpecting('product-listing-route-category-id'));

        $result = $decorator->load('category-id', new Request(), $this->context(), new Criteria());

        static::assertSame($response, $result);
    }

    public function testReviewRouteEmitsLegacyTagForProductId(): void
    {
        $response = $this->createMock(ProductReviewRouteResponse::class);

        $inner = $this->createMock(AbstractProductReviewRoute::class);
        $inner->expects(static::once())->method('load')->willReturn($response);

        $decorator = new ProductReviewRouteTagDecorator($inner, $this->dispatcherExpecting('product-review-route-product-id'));

        $result = $decorator->load('product-id', new Request(), $this->context(), new Criteria());

        static::assertSame($response, $result);
    }

    public function testCrossSellingRouteEmitsLegacyTagForProductId(): void
    {
        $response = $this->createMock(ProductCrossSellingRouteResponse::class);

        $inner = $this->createMock(AbstractProductCrossSellingRoute::class);
        $inner->expects(static::once())->method('load')->willReturn($response);

        $decorator = new ProductCrossSellingRouteTagDecorator($inner, $this->dispatcherExpecting('cross-selling-route-product-id'));

        $result = $decorator->load('product-id', new Request(), $this->context(), new Criteria());

        static::assertSame($response, $result);
    }

    public function testGetDecoratedReturnsInner(): void
    {
        $inner = $this->createMock(AbstractProductDetailRoute::class);
        $decorator = new ProductDetailRouteTagDecorator($inner, $this->createMock(EventDispatcherInterface::class));

        static::assertSame($inner, $decorator->getDecorated());
    }

    private function dispatcherExpecting(string $expectedTag): EventDispatcherInterface
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function (AddCacheTagEvent $event) use ($expectedTag): bool {
                return \in_array($expectedTag, $event->tags, true);
            }))
            ->willReturnArgument(0);

        return $dispatcher;
    }

    private function context(): SalesChannelContext
    {
        return $this->createMock(SalesChannelContext::class);
    }
}
